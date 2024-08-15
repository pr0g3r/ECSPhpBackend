<template>
  <el-dialog title="Return Modal " fullscreen :model-value="visible" @close="emit('modal-close')">
    <el-container>
      <el-main style="width: 50%">
        <el-divider>Order Details</el-divider>
        <el-form
          id="form"
          style="max-width: 90%"
          label-width="120px"
          ref="formRef"
          :model="order"
          :rules="rules"
          @submit.prevent="handleForm"
        >
          <el-form-item prop="order" label="Order No">
            <el-input readonly v-model="order.order" />
          </el-form-item>

          <el-form-item prop="source" label="Source">
            <el-input readonly v-model="order.source" />
          </el-form-item>

          <el-form-item prop="courier" label="Courier">
            <el-input readonly v-model="order.courier" />
          </el-form-item>

          <el-form-item prop="tracking_id" label="Tracking">
            <el-input readonly v-model="order.tracking_id" />
          </el-form-item>

          <el-form-item prop="date" label="Date">
            <el-date-picker
              type="date"
              style="width: 100%"
              format="YYYY/MM/DD"
              value-format="YYYY-MM-DD"
              v-model="order.date"
            />
          </el-form-item>

          <el-form-item prop="name" label="Name">
            <el-input v-model="order.name" />
          </el-form-item>

          <el-form-item prop="reason" label="Reason">
            <el-select filterable style="width: 100%" v-model="order.reason">
              <el-option v-for="(option, index) in selects.reason" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="option" label="Option">
            <el-select filterable style="width: 100%" v-model="order.option">
              <el-option v-for="(option, index) in selects.option" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="created" label="Created">
            <el-date-picker
              type="date"
              style="width: 100%"
              format="YYYY/MM/DD"
              value-format="YYYY-MM-DD"
              v-model="order.created"
            />
          </el-form-item>

          <el-form-item prop="action_customer" label="Customer">
            <el-select filterable style="width: 100%" v-model="order.action_customer">
              <el-option
                v-for="(option, index) in selects.action_customer"
                :key="index"
                :value="option"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Notes">
            <el-input type="textarea" placeholder="Notes" :rows="3" v-model="order.notes" />
          </el-form-item>
        </el-form>
      </el-main>

      <el-main style="width: 50%">
        <el-divider>Order Items</el-divider>
        <item-builder actions />
      </el-main>
    </el-container>

    <el-divider></el-divider>
    <el-footer class="ModalBtns">
      <el-button type="success" native-type="submit" form="form">Submit</el-button>

      <el-button @click="emit('modal-close')">Cancel</el-button>
    </el-footer>
  </el-dialog>
</template>

<script setup>
/* Core Imports. */
import { onMounted, reactive, ref } from "vue";

/* Component Imports. */
import ItemBuilder from "./ItemBuilder.vue";

/* Util Imports. */
import rules from "../utils/rules";
import request from "../utils/request";

/* Component Properties. */
const props = defineProps({
  visible: {
    type: Boolean,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  order: {
    type: Object,
    required: true,
  },
});

/* Component Events. */
const emit = defineEmits(["modal-close", "modal-update"]);

/* Component Variables. */
const selects = reactive({
  reason: [],
  option: [],
  action_customer: [],
});
const formRef = ref("");

/* Validate form inputs. */
function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      emit("modal-update", props.order);
    } else {
      console.log("INVALID!");
    }
  });
}

/* Collect Options For Form Selects. */
onMounted(() => {
  request
    .get(`orders/order_form_options/${Object.keys(selects).join("/")}`)
    .then((res) => Object.assign(selects, res.data));
});
</script>
