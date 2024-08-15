<template>
  <el-container>
    <el-header height="65px">
      <filter-set
        :filterSets="[
          'source',
          'courier',
          'reason',
          'option',
          'action_customer',
        ]"
        @url-args="(args) => main.buildRequest('returns', args)"
      />
    </el-header>

    <el-main>
      <el-table
        :data="main.orders"
        style="width: 100%"
        stripe
        @sort-change="setOrdering"
      >
        <el-table-column prop="order" label="Order" width="200" />
        <el-table-column prop="source" label="Source" width="100" />
        <el-table-column prop="courier" label="Courier" width="75" />
        <el-table-column prop="tracking_id" label="Tracking" width="175" />
        <el-table-column prop="name" label="Name" width="125" show-overflow-tooltip />
        <el-table-column prop="reason" label="Reason" width="150" />
        <el-table-column prop="option" label="Option" width="200" show-overflow-tooltip />
        <el-table-column prop="created" label="Created" width="100" sortable="custom" />
        <el-table-column prop="action_customer" label="Action" width="150" />
        <el-table-column prop="items" label="Items" width="150" show-overflow-tooltip />
        <el-table-column prop="notes" label="Notes" width="200" show-overflow-tooltip />

        <el-table-column fixed="right" label="Operations" width="100">
          <template #default="scope">
            <el-button type="primary" size="small" @click="modal.modalTargetOrder('return', scope.$index, scope.row, true)">Edit</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-main>

    <return-modal
      :visible="modal.visible"
      :index="modal.index"
      :order="modal.order"
      @modal-close="modal.modalVisible(false)"
      @modal-update="updateReturn"
    />
  </el-container>
</template>

<script setup>
/* Core Imports. */
import { onMounted } from "vue";

/* Component Imports. */
import FilterSet from "../components/FilterSet.vue";
import ReturnModal from "../components/ReturnModal.vue";

/* Library Imports. */
import { useMain, useModal } from "../store";
import { clone, each, find, difference } from "lodash";

/* Util Imports. */
import error from "../utils/error";
import request from "../utils/request";
import { setOrdering } from "../utils/generic";
import { fespRequest } from "../utils/fespUtils";
import { orderSerializer } from "../utils/serializers";

/* Initialize Stores. */
const main = useMain();
const modal = useModal();

/* View Variables. */
/* Actions that requrie a request to the backend. */
const specialActions = {
  Restocked: "addBackToStock",
};

/**
 * Event handler for the return-modal component.
 *
 * Recieve the updated target row and dispatches requests to update backend.
 *
 * @param {Object} record Updated return object from return-modal.
 */
function updateReturn(record) {
  modal.modalVisible(false); // Hide modal.

  /* Update main store with updated record returned from modal. */
  main.$patch((state) => {
    state.orders[modal.index] = record;
  });

  /* Dispatch delete events for removed items. */
  let removed = difference(modal.originalItems, modal.items);
  each(removed, (item) => {
    if (item.id) {
      request.delete(`return_items/${item.id}/`);
    }
  });

  /* Update order record. */
  request
    .patch(`orders/${record.order}/`, orderSerializer(record))
    .catch((err) => error(err));

  /* Update return record. */
  request
    .patch(`returns/${record.order}/`, record)
    .catch((err) => error(err));

  /* Dispatch special actions for new or edited items. */
  each(modal.items, (item) => {
    let itemAction = item.action_product;

    /* Only need to dispatch events for special events. */
    if (specialActions[itemAction]) {
      let originalItem = find(modal.originalItems, { sku: item.sku });

      /* Dispatch if new item, or the action applied has changed. */
      if (!originalItem || originalItem.action_product !== itemAction) {
        fespRequest("StockController", specialActions[itemAction], [
          [
            {
              orderID: record.order,
              sku: item.sku,
              qty: item.action_qty,
            },
          ],
        ]).catch((err) => error(err));
      }
    }

    if (item.id) {
      request
        .put(`return_items/${item.id}/`, item)
        .catch((err) => error(err)); // Update existing item.
    } else {
      /* Insert new item. */
      request
        .post("return_items/", {
          order: record.order,
          ...item,
        })
        .catch((err) => error(err));
    }
  });

  modal.$reset();
}

/* Reset current page on mount. */
onMounted(() => main.$reset());
</script>

<style scoped>
.el-header {
  padding: 1rem;
}
</style>
