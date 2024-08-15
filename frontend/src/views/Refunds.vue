<template>
  <el-container>
    <el-header height="65px">
      <filter-set
        :filterSets="[
          'source',
          'courier',
          'reason',
          'option',
        ]"
        @url-args="(args) => main.buildRequest('refunds', args)"
      />
    </el-header>

    <el-main>
      <el-table :data="main.orders" style="width: 100%" stripe @sort-change="setOrdering">
        <el-table-column prop="order" label="Order" width="200" />
        <el-table-column prop="source" label="Source" width="100" />
        <el-table-column prop="courier" label="Courier" width="75" />
        <el-table-column prop="tracking_id" label="Tracking" width="175" />
        <el-table-column prop="name" label="Name" width="125" show-overflow-tooltip />
        <el-table-column prop="reason" label="Reason" width="150" />
        <el-table-column prop="option" label="Option" width="150" show-overflow-tooltip />
        <el-table-column prop="created" label="Created" width="100" sortable="custom" />
        <el-table-column prop="full_refund" label="Refund" width="100" sortable="custom" />
        <el-table-column prop="amount" label="Amount" width="100" sortable="custom" />
        <el-table-column prop="void_order" label="Void" width="100" sortable="custom" />
        <el-table-column prop="dor" label="DOR" width="100" sortable="custom" />
        <el-table-column prop="notes" label="Notes" width="150" show-overflow-tooltip />

        <el-table-column fixed="right" label="Operations" width="95">
          <template #default="scope">
            <el-button
              type="primary"
              size="small"
              @click="modal.modalTargetOrder('refund', scope.$index, scope.row, false)"
            >Edit</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-main>

    <refund-modal
      :visible="modal.visible"
      :index="modal.index"
      :order="modal.order"
      @modal-close="modal.modalVisible(false)"
      @modal-update="updateRefund"
    />
  </el-container>
</template>

<script setup>
/* Core Imports. */
import { onMounted } from "vue";

/* Component Imports. */
import RefundModal from "../components/RefundModal.vue";
import FilterSet from "../components/FilterSet.vue";

/* Library Imports. */
import { useMain, useModal } from "../store";

/* Util Imports. */
import request from "../utils/request";
import error from "../utils/error";
import { setOrdering } from "../utils/generic";
import { fespRequest } from "../utils/fespUtils";

/* Initialize Store. */
const main = useMain();
const modal = useModal();

function updateRefund(record) {
  // Hide modal.
  modal.modalVisible(false);

  // Update the record displayed in Refunds view table.
  main.$patch((state) => {
    state.orders[modal.index] = record;
  });

  // Update status on fesp.
  let status = record.void_order === true ? "VOID" : "MARKED";
  fespRequest("OrderController", "setOrderStatus", [
    [
      {
        orderID: record.order,
        status: status,
      },
    ],
  ]);

  /* Update refund record. */
  request.patch(`refunds/${record.order}/`, record).catch((err) => error(err));

  // Update completed, reset modal state.
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
