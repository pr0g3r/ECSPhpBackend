<template>
  <el-container>
    <el-header height="65px">
      <filter-set
        :filterSets="[
          'courier',
          'reason',
          'option',
          'room',
          'picker',
          'packer',
        ]"
        @url-args="(args) => main.buildRequest('pending_resends', args)"
      />
    </el-header>

    <el-main>
      <el-table :data="main.orders" style="width: 100%" stripe @sort-change="setOrdering">
        <el-table-column prop="order" label="Order" width="200" />
        <el-table-column prop="courier" label="Courier" width="100" />
        <el-table-column prop="name" label="Name" width="200" />
        <el-table-column prop="reason" label="Reason" width="150" />
        <el-table-column prop="option" label="Option" width="200" />
        <el-table-column prop="room" label="Room" width="75" />
        <el-table-column prop="created" label="Created" width="125" sortable="custom" />
        <el-table-column prop="dor" label="Dor" width="75" sortable="custom" />
        <el-table-column prop="items" label="Items" width="200" show-overflow-tooltip />
        <el-table-column prop="notes" label="Notes" width="200" show-overflow-tooltip />

        <el-table-column fixed="right" label="Operations" width="250">
          <template #default="scope">
            <el-button
              type="primary"
              size="small"
              @click="modal.modalTargetOrder('pending', scope.$index, scope.row, true)"
            >Edit</el-button>

            <el-button
              type="success"
              size="small"
              @click="approvePending(scope.$index, scope.row)"
            >Approve</el-button>

            <el-button
              type="danger"
              size="small"
              @click="deletePending(scope.$index, scope.row.order)"
            >Delete</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-main>

    <pending-modal
      :visible="modal.visible"
      :index="modal.index"
      :order="modal.order"
      @modal-close="modal.modalVisible(false)"
      @modal-update="updatePending"
    />
  </el-container>
</template>

<script setup>
/* Core Imports. */
import { onMounted } from "vue";

/* Component Imports. */
import FilterSet from "../components/FilterSet.vue";
import PendingModal from "../components/PendingModal.vue";

/* Library Imports. */
import { useMain, useModal } from "../store";
import { ElMessageBox, ElMessage } from "element-plus";
import { clone, each, upperFirst, difference } from "lodash";

/* Util Imports. */
import error from "../utils/error";
import request from "../utils/request";
import { setOrdering } from "../utils/generic";
import { fespRequest, fespCreateOrder } from "../utils/fespUtils";
import { resendSerializer } from "../utils/serializers";

/* Initialize Stores. */
const main = useMain();
const modal = useModal();

/**
 * Event handler for pending-modal component.
 *
 * Updates all information for the pending record and all of its items.
 *
 * @param {Object} record Updated pending record passed from pending-modal.
 */
function updatePending(record) {
  modal.modalVisible(false); // Hide modal.

  /* Update the targeted row in the main store. */
  main.$patch((state) => {
    state.orders[modal.index] = record;
  });

  /* Dispatch delete events for removed items. */
  let removed = difference(modal.originalItems, modal.items);
  each(removed, (item) => {
    if (item.id) {
      request.delete(`pending_items/${item.id}/`);
    }
  });

  /* Update pending record. */
  request
    .patch(`pending_resends/${record.order}/`, record)
    .catch((err) => error(err));

  /* Dispatch item insert/updates. */
  each(modal.items, (item) => {
    if (item.id) {
      request
        .put(`pending_items/${item.id}/`, item)
        .catch((err) => error(err));
    } else {
      request
        .post("pending_items/", {
          order: record.order,
          ...item,
        })
        .catch((err) => error(err));
    }
  });

  modal.$reset();
}

/**
 * Approve the pending order.
 *
 * This will move the pending record into the resends table, then generate the order
 * on fesp and print the invoice.
 *
 * @param {Number} index Table index of the targeted record.
 * @param {Object} index Table row data of the targeted record.
 */
function approvePending(index, record) {
  ElMessageBox.confirm(
    "Approving this order will generate an order on FESP and move this record from pending to resends. Continue?",
    "Warning",
    {
      confirmButtonText: "OK",
      cancelButtonText: "Cancel",
      type: "warning",
    }
  ).then(() => {

    /* Check if the original order exists more than once,
       if so then we need to make it so only a super user can approve. */

    
      /* Move order from pending to resends. */
      request.get(`pending_items/${record.order}/`).then((res) => {
        let orderItems = res.data;

        /* Retrieve the order details for the order (original). */
        fespRequest("OrderController", "getOrderContent", [
          record.original_order,
          true,
        ]).then((res) => {
          /* Create the order in fesp. */
          let parentOrder = res.data[record.original_order];
          let order = {
            "Order Number": record.order,
            Courier: record.courier,
            Channel: parentOrder.channel,
            "Delivery Name": parentOrder.shipping.name,
            "Address Line 1": parentOrder.shipping.address1,
            "Address Line 2": parentOrder.shipping.address2,
            City: parentOrder.shipping.city,
            County: parentOrder.shipping.county,
            Country: parentOrder.shipping.countryCode,
            Postcode: parentOrder.shipping.postCode,
            "Phone No": parentOrder.phone,
            Email: parentOrder.email,
            Notes: record.notes,
            "No. Of Labels": parentOrder.parcelCount,
            Weight: parentOrder.weight,
            Length: parentOrder.length,
            items: orderItems,
          };

          let ecsRecord = {
            order_id: record.order,
            source: upperFirst(parentOrder.source),
            courier: record.courier,
            tracking_id: parentOrder.tracking_id,
            date: new Date().toISOString().split("T")[0],
            name: parentOrder.shipping.name,
            contact: parentOrder.email,
          };

          request.post("orders/", ecsRecord).finally(() => {
            request.post("resends/", resendSerializer(record));

            request.delete(`pending_resends/${record.order}/`);

            fespRequest("OrderController", "serializeFespOrder", [order]).then(
              (res) => {
                fespCreateOrder(res.data);
              }
            );
          });
        });
      });

      /* Remove the order from view. */
      main.orders.splice(index, 1);

      /* Notify user of status of operation. */
      ElMessage({
        type: "success",
        message: "Completed",
      });
    })
    .catch(() => {
      ElMessage({
        type: "info",
        message: "Canceled",
      });
    });
}

/**
 * Delete the pending order.
 *
 * This will remove the pending record and its items from the system.
 *
 * @param {Number} index Table index of the targeted record.
 * @param {Any} order Order Number to be deleted.
 */
function deletePending(index, order) {
  ElMessageBox.confirm("Delete this order?", "Warning", {
    confirmButtonText: "OK",
    cancelButtonText: "Cancel",
    type: "warning",
  })
    .then(() => {
      request.delete(`pending_resends/${order}/`);
      // main.orders[index].splice(index, 1);
      delete main.orders[index];

      ElMessage({
        type: "success",
        message: "Pending record deleted",
      });
    })
    .catch(() => {
      ElMessage({
        type: "info",
        message: "Canceled",
      });
    });
}

/* Reset current page on mount. */
onMounted(() => main.$reset());
</script>

<style scoped>
.el-header {
  padding: 1rem;
}
</style>
