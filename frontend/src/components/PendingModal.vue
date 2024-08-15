<template>
  <el-dialog title="Pending Modal" fullscreen :model-value="visible" @close="emit('modal-close')">
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

          <el-form-item prop="created" label="Created">
            <el-date-picker
              type="date"
              style="width: 100%"
              format="YYYY/MM/DD"
              value-format="YYYY-MM-DD"
              v-model="order.created"
            />
          </el-form-item>

          <el-form-item prop="courier" label="Courier">
            <el-select filterable style="width: 100%" v-model="order.courier">
              <el-option v-for="(option, index) in selects.courier" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="reason" label="Reason">
            <el-select filterable style="width: 100%" v-model="order.reason">
              <el-option v-for="(option, index) in selects.reason" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="room" label="Room">
            <el-select filterable style="width: 100%" v-model="order.room">
              <el-option v-for="(option, index) in selects.room" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="picker" label="Picker">
            <el-select filterable style="width: 100%" v-model="order.picker">
              <el-option value>None</el-option>
              <el-option v-for="(option, index) in selects.warehouse" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="packer" label="Packer">
            <el-select filterable style="width: 100%" v-model="order.packer">
              <el-option value>None</el-option>
              <el-option v-for="(option, index) in selects.warehouse" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item label="Notes">
            <el-input type="textarea" placeholder="Notes" :rows="3" v-model="order.notes" />
          </el-form-item>
        </el-form>
      </el-main>

      <el-main style="width: 50%">
        <el-divider>Order Items</el-divider>
        <item-builder />
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
import { computed, onMounted, reactive, ref } from "vue";

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
  room: [],
  warehouse: [],
  courier: [],
});
const formRef = ref("");

/* Validate form inputs. */
function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      props.order.dor = checkDor.value;
      emit("modal-update", props.order);
    } else {
      console.log("INVALID!");
    }
  });
}

const checkDor = computed(
  () =>
    props.order.reason === "Customer" ||
    props.order.reason === "Lost In Transit"
);

/* Collect Options For Form Selects. */
onMounted(() => {
  request
    .get(`orders/order_form_options/${Object.keys(selects).join("/")}`)
    .then((res) => Object.assign(selects, res.data));
});
</script>
