<template>
  <el-container>
    <el-main>
      <el-form
        label-width="120px"
        style="max-width: 100%"
        ref="formRef"
        :model="form"
        :rules="rules"
        @submit.prevent="addItem"
      >
        <el-form-item :prop="skuType ? 'sku' : 'ghostSku'" :label="skuType ? 'Sku' : 'Ghost Sku'">
          <el-select-v2
            v-if="skuType"
            style="width: 85%"
            filterable
            v-model="form.sku"
            :options="selects.skus"
          />

          <el-select v-if="!skuType" style="width: 85%" filterable v-model="form.ghostSku">
            <el-option
              v-for="(option, index) in selects.ghost"
              :key="index"
              :value="option.title"
              @click="populateTitle"
            />
          </el-select>
          <el-button @click="skuType = !skuType">Toggle</el-button>
        </el-form-item>

        <el-form-item prop="title" label="Title">
          <el-input style="width: 95%" v-model="form.title" />
        </el-form-item>

        <el-form-item prop="qty" label="Qty">
          <el-input-number :min="1" v-model="form.qty" />

          <el-form-item prop="price" label="Price">
            <el-input-number :precision="2" :min="0.00" :step="0.01" v-model="form.price" />
          </el-form-item>

          <el-form-item prop="shipping" label="Shipping">
            <el-input-number :precision="2" :min="0.00" :step="0.01" v-model="form.shipping" />
          </el-form-item>
        </el-form-item>

        <el-form-item v-if="actions" prop="action_product" label="Action">
          <el-select filterable style="width: 58%" v-model="form.action_product">
            <el-option
              v-for="(option, index) in selects.actions"
              :key="index"
              :value="option.name"
            />
          </el-select>

          <el-form-item prop="action_qty" label="Action Qty">
            <el-input-number :required="actions" :min="1" v-model="form.action_qty" />
          </el-form-item>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" native-type="submit">Add</el-button>

          <el-button type="danger" @click="clearItems()">Clear</el-button>
        </el-form-item>
      </el-form>

      <el-table :data="modal.items">
        <el-table-column
          prop="sku"
          label="Sku"
          :width="props.actions ? 100 : 150"
          show-overflow-tooltip
        />
        <el-table-column
          prop="title"
          label="Title"
          :width="props.actions ? 100 : 150"
          show-overflow-tooltip
        />
        <el-table-column prop="qty" label="Qty" :width="props.actions ? 75 : 110" />
        <el-table-column prop="price" label="Price" :width="props.actions ? 75 : 110" />
        <el-table-column prop="shipping" label="Shipping" :width="props.actions ? 75 : 110" />

        <el-table-column v-if="actions" prop="action_product" label="Action" width="100" />
        <el-table-column v-if="actions" prop="action_qty" label="Action Qty" width="100" />

        <el-table-column fixed="right" label="Operations" :width="props.actions ? 200 : 240">
          <template #default="scope">
            <el-button size="small" @click="editItem(scope.$index, scope.row)">Edit</el-button>

            <el-button type="danger" size="small" @click="modal.deleteItem(scope.$index)">Delete</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-main>

    <item-modal
      :visible="itemModal.visible"
      :index="itemModal.index"
      :item="itemModal.data"
      :actions="actions ? selects.actions : false"
      @modal-close="itemModal.visible = false"
      @modal-submit="itemModalEdit"
    />
  </el-container>
</template>

<script setup>
/* Core Imports. */
import { onMounted, reactive, ref, watch } from "vue";

/* Component Imports. */
import ItemModal from "./ItemModal.vue";
import { ElMessageBox, ElMessage } from "element-plus";

/* Library Imports. */
import axios from "axios";
import { clone } from "lodash";
import { useMain, useModal } from "../store";

/* Util Imports. */
import rules from "../utils/rules";
import { fespQuery, fespRequest } from "../utils/fespUtils";

/* Component Properties. */
const props = defineProps({
  actions: {
    type: Boolean,
    required: false,
  },
});

/* Initialize Stores. */
const main = useMain();
const modal = useModal();

/* Component Variables. */
const selects = reactive({
  skus: [],
  ghost: [],
  prices: [],
  actions: [],
});
const formRef = ref("");
const form = reactive({
  sku: "",
  ghostSku: "",
  title: "",
  qty: 0,
  shipping: 0.0,
  price: 0.0,
  action_product: "",
  action_qty: 0,
});
const skuType = ref(true);
const itemModal = reactive({
  visible: false,
  index: 0,
  data: {},
});

/* Convert array to format for virtual select component. */
function arrayToVirt(arr) {
  return arr.map((item) => {
    return {
      value: `${item}`,
      label: `${item}`,
    };
  });
}

/**
 * Emit handler for the item-modal component.
 *
 * Passes the index and the updated item to the modal store editItem funciton.
 *
 * @param {Object} item Updated item object.
 */
function itemModalEdit(item) {
  itemModal.visible = false;
  modal.editItem(itemModal.index, item);
}

/**
 * Format and emit item to parent
 *
 * @return Emits item object back to parent
 */
function addItem() {
  formRef.value.validate((valid) => {
    if (valid) {
      const item = {
        sku: skuType.value ? form.sku : form.ghostSku,
        title: form.title,
        qty: form.qty,
        shipping: form.shipping,
        price: form.price,
        action_product: form.action_product,
        action_qty: form.action_qty,
      };
      modal.addItem(item);
      return formRef.value.resetFields();
    } else {
      console.log("INVALID!");
    }
  });
}

/* Pass targeted item to item modal for editing */
function editItem(index, row) {
  itemModal.index = index;
  itemModal.data = clone(row);
  itemModal.visible = true;
}

/* Reset form fields to default values. */
function resetForm() {
  form.sku = "";
  form.ghostSku = "";
  form.title = "";
  form.qty = 1;
  form.shipping = 0.0;
  form.price = 0.0;
  form.action_qty = 1;
}

/* Prompt user to confirm they wish to clear current items. */
function clearItems() {
  ElMessageBox.confirm("Clear items for this order. Continue?", "Warning", {
    cancelButtonText: "Cancel",
    confirmButtonText: "OK",
    type: "danger",
  })
    .then(() => {
      ElMessage({
        type: "success",
        message: "Items Cleared",
      });
      modal.clearItems();
    })
    .catch(() => {
      ElMessage({
        type: "info",
        message: "Cancelled",
      });
    });
}

/* Automatically set price field when a user selects a sku. */
watch(
  () => [form.sku, form.ghostSku],
  () => {
    const selectedSku = skuType.value ? form.sku : form.ghostSku;

    if (selectedSku) {
      const skuPrice = selects.prices[selectedSku];
      form.price = skuPrice ? Number(skuPrice) : 0.0;
    }
  }
);

function populateTitle() {
  let match = Object.values(selects.ghost).find(
    (row) => row.title === form.ghostSku
  );

  form.ghostSku = match.sku;
  form.title = match.title;
}

onMounted(() => {
  axios
    .all([
      fespQuery("stock", ["sku"], "sku_atts", 7),
      fespQuery("stock", ["sku", "title"], "ghost_sku", 2),
      fespRequest("StockController", "getSkuPrices", [null, ["web"]]),
      axios.get(import.meta.env.VITE_PHP_API_URL + "action_products"),
    ])
    .then(
      axios.spread((skus, ghost, prices, actions) => {
        selects.skus = arrayToVirt(skus.data);
        // selects.ghost = Object.keys(ghost.data);
        selects.ghost = ghost.data;
        selects.prices = prices.data.web;
        selects.actions = actions.data.results;
      })
    );
});
</script>

<style scoped>
</style>
