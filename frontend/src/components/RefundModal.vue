<template>
  <el-dialog title="Refund Modal" fullscreen :model-value="visible" @close="emit('modal-close')">
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
          @submit.prevent="handleForm()"
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

          <el-form-item prop="name" label="Name">
            <el-input readonly v-model="order.name" />
          </el-form-item>

          <el-form-item prop="date" label="Date">
            <el-date-picker
              type="date"
              style="width: 100%"
              format="YYYY/MM/DD"
              value-format="YYYY-MM-DD"
              v-model="order.created"
            />
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

          <el-form-item prop="full_refund" label="Full Refund">
            <el-switch
              v-model="order.full_refund"
              class="ml-2"
              active-color="#13ce66"
              inactive-color="#ff4949"
            />
          </el-form-item>

          <el-form-item prop="void_order" label="Void Order">
            <el-switch
              v-model="order.void_order"
              class="ml-2"
              active-color="#13ce66"
              inactive-color="#ff4949"
            />
          </el-form-item>

          <el-form-item v-if="!order.full_refund" prop="amount" label="Amount">
            <el-input-number :precision="2" :min="0.00" :step="0.01" v-model="order.amount" />
          </el-form-item>
          
          <el-form-item label="Notes">
            <el-input type="textarea" placeholder="Notes" :rows="3" v-model="order.notes" />
          </el-form-item>
        </el-form>
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
import { onMounted, reactive, ref } from "vue";
import request from "../utils/request";
import rules from "../utils/rules";

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
});
const formRef = ref("")

/* Validate form inputs. */
function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      emit("modal-update", props.order);
    }
    else {
      console.log("INVALID!");
    }
  })
}

onMounted(() => {
  /* Retireve all required lookup options for the form. */
  request
    .get(`orders/order_form_options/${Object.keys(selects).join("/")}`)
    .then((res) => Object.assign(selects, res.data));
})
</script>
