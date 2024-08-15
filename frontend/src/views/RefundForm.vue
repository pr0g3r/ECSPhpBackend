<template>
  <div>
    <el-header style="text-align: center">
      <h1>Refund Form</h1>
    </el-header>

    <el-divider></el-divider>
    <el-container>
      <el-main style="width: 50%">
        <el-divider>Refund Details</el-divider>
        <el-form
          id="form"
          label-width="120px"
          style="max-width: 25%"
          ref="formRef"
          :model="form"
          :rules="rules"
          @submit.prevent="handleForm()"
        >
          <el-form-item prop="reason" label="Reason">
            <el-select filterable style="width: 100%" v-model="form.reason">
              <el-option v-for="(option, index) in selects.reason" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="option" label="Option">
            <el-select filterable style="width: 100%" v-model="form.option">
              <el-option v-for="(option, index) in selects.option" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="full_refund" label="Full Refund">
            <el-switch
              v-model="form.full_refund"
              class="ml-2"
              active-color="#13ce66"
              inactive-color="#ff4949"
            />
          </el-form-item>

          <el-form-item prop="void_order" label="Void">
            <el-switch
              v-model="form.void_order"
              class="ml-2"
              active-color="#13ce66"
              inactive-color="#ff4949"
            />
          </el-form-item>

          <el-form-item v-if="!form.full_refund" prop="amount" label="Amount">
            <el-input-number :precision="2" :min="0.00" :step="0.01" v-model="form.amount" />
          </el-form-item>

          <el-form-item label="Notes">
            <el-input type="textarea" placeholder="Notes" :rows="3" v-model="form.notes" />
          </el-form-item>

          <el-form-item prop="attachments" label="Attachments">
            <el-upload
              action
              :on-change="modal.addAttachment"
              :on-remove="modal.removeAttachment"
              :auto-upload="false"
              :file-list="modal.attachements"
              list-type="picture"
              mutliple
            >
              <el-button type="primary" size="small" plain @click="modal.attachType = 'misc'">Misc</el-button>
              <el-button
                type="primary"
                size="small"
                plain
                @click="modal.attachType = 'del'"
              >Delivery</el-button>
              <el-button type="primary" size="small" plain @click="modal.attachType = 'dor'">DOR</el-button>
              <template #tip>
                <div class="el-upload__tip">jpg/png files with a size less than 500kb</div>
              </template>
            </el-upload>
          </el-form-item>
        </el-form>
      </el-main>
    </el-container>

    <el-divider></el-divider>
    <el-footer class="ModalBtns">
      <el-button type="success" native-type="submit" form="form">Submit</el-button>
    </el-footer>
  </div>
</template>

<script setup>
/* Core Imports. */
import { computed, onMounted, reactive, ref } from "vue";

/* Library Imports. */
import { useModal } from "../store";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";

/* Util Imports. */
import rules from "../utils/rules";
import error from "../utils/error";
import request from "../utils/request";
import { fespRequest } from "../utils/fespUtils";
import { orderSerializer, refundSerializer } from "../utils/serializers";
import { checkOrderExists } from "../utils/generic";

/* View Properties. */
const props = defineProps({
  order_id: {
    type: String,
    requried: true,
  },
});

/* Initialize Router. */
const router = useRouter();

/* Initialize Stores. */
const modal = useModal();

/* View Variables. */
const selects = reactive({
  reason: [],
  option: [],
  room: [],
  warehouse: [],
  courier: [],
});
const form = reactive({
  order: props.order_id,
  created: new Date().toISOString().split("T")[0],
  reason: "",
  option: "",
  order_total: "",
  full_refund: false,
  amount: 0.0,
  void_order: false,
  notes: null,
  dor: computed(
    () => form.reason === "Customer" || form.reason === "Lost In Transit"
  ),
});
const formRef = ref("");

/* Create new pending record. */
function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      // Create base order record if it doesn't exist.
      request
        .post("orders/", orderSerializer(modal.baseOrder))
        .then(() => createRefund())
        .catch(() => createRefund());
    } else {
      console.log("INVALID!");
    }
  });

  return router.push("/");
}

// Create Refund record.
function createRefund() {
  request
    .post("refunds/", refundSerializer(form, modal.baseOrder.total))
    .then(() => {
      if (form.void_order) {
        fespRequest("OrderController", "setOrderStatus", [
          [
            {
              orderID: props.order_id,
              status: "VOID",
            },
          ],
        ]);
      }

      /* Store the attachements for the order. */
      modal.handleAttachments(form.order, "Refund");
    })
    .catch((err) => error(err));
}

onMounted(() => {
  checkOrderExists("refunds", props.order_id);
  modal.$reset();

  /* Retireve all required lookup options for the form. */
  request
    .get(`orders/order_form_options/${Object.keys(selects).join("/")}`)
    .then((res) => Object.assign(selects, res.data));

  /* Get the original order information from fesp. */
  fespRequest("OrderController", "getOrderContent", [
    props.order_id,
    true,
  ]).then((res) => {
    if (res.data.length < 1) {
      ElMessage({
        message: "No Order Matching This Id Exists In Fesp !",
        type: "warning",
        duration: 7 * 1000,
      });
      return router.push("/");
    }

    modal.baseOrder = res.data[props.order_id];
  });
});
</script>
