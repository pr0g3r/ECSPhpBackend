<template>
  <div>
    <el-header style="text-align: center">
      <h1>Return Form</h1>
    </el-header>

    <el-divider></el-divider>
    <el-container>
      <el-main style="width: 50%">
        <el-divider>Return Details</el-divider>
        <el-form
          id="form"
          label-width="120px"
          style="max-width: 90%"
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

          <el-form-item prop="action_customer" label="Action">
            <el-select filterable style="width: 100%" v-model="form.action_customer">
              <el-option
                v-for="(option, index) in selects.action_customer"
                :key="index"
                :value="option"
              />
            </el-select>
          </el-form-item>

          <el-form-item v-if="showFullRefund" prop="full_refund" label="Full Refund">
            <el-switch
              v-model="form.full_refund"
              class="ml-2"
              active-color="#13ce66"
              inactive-color="#ff4949"
            />
          </el-form-item>

          <el-form-item v-if="showVoid" prop="void_order" label="Void">
            <el-switch
              v-model="form.void_order"
              class="ml-2"
              active-color="#13ce66"
              inactive-color="#ff4949"
            />
          </el-form-item>

          <el-form-item v-if="showAmount" prop="amount" label="Amount">
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

      <el-main style="width: 50%">
        <el-divider>Return Items</el-divider>
        <item-builder actions />
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

/* Component Imports. */
import ItemBuilder from "../components/ItemBuilder.vue";

/* Library Imports. */
import { useModal } from "../store";
import { useRouter } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";

/* Util Imports. */
import rules from "../utils/rules";
import error from "../utils/error";
import request from "../utils/request";
import { fespRequest } from "../utils/fespUtils";
import { checkOrderExists } from "../utils/generic";
import {
  orderSerializer,
  fespItemSerializer,
  returnSerializer,
  refundSerializer,
} from "../utils/serializers";

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
  action_customer: [],
});
const form = reactive({
  order: props.order_id,
  created: new Date().toISOString().split("T")[0],
  reason: "",
  option: "",
  action_customer: "",
  full_refund: false,
  amount: 0.0,
  void_order: false,
  notes: null,
  dor: computed(
    () =>
      form.action_customer === "Refunded" &&
      form.option === "No Reason" &&
      modal.baseOrder.courier === "HI"
  ),
});
const formRef = ref("");

// Boolean property that toggles the visibility of the full refund from input.
const showFullRefund = computed(() => {
  if (form.action_customer === "Refunded") return true;

  // Reset.
  form.full_refund = false;
  return false;
});

// Boolean property that toggles the visibility of the refund amount input.
const showAmount = computed(
  () => form.action_customer === "Refunded" && !form.full_refund
);

// Boolean property that toggles the visibility of the void order form input.
const showVoid = computed(() => {
  if (form.action_customer === "Refunded") return true;

  // Reset.
  form.void_order = false;
  return false;
});

function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      request
        .post("orders/", orderSerializer(modal.baseOrder))
        .then(() => createReturn())
        .catch(() => createReturn());
    }
  });

  return router.push("/");
}

// Create Return record.
function createReturn() {
  request
    .post("returns/", returnSerializer(form))
    .then(() => {
      /* Insert the items */
      modal.items.forEach((item) => {
        request.post("return_items/", {
          order: props.order_id,
          sku: item.sku,
          qty: item.qty,
          action_qty: item.action_qty,
          action_product: item.action_product,
          title: item.title,
          price: item.price,
          shipping: item.shipping,
        });

        // Dispatch restock call if item action is restocked
        if (item.action_product === "Restocked") {
          fespRequest("StockController", "addBackToStock", [
            [
              {
                orderID: props.order_id,
                sku: item.sku,
                qty: item.qty,
              },
            ],
          ]).catch((err) => error(err));
        }
      });

      /* Store the attachements for the order. */
      modal.handleAttachments(form.order, "Return");
    })
    .catch((err) => error(err));

  if (form.action_customer === "Refunded") {
    createRefund();
  }
  // Ask user if they would like to create a resend record.
  else if (form.action_customer === "Replacement") {
    request
      .get(`resends/${props.order_id}`)
      .then((res) => {
        if (res.status !== 200) {
          ElMessageBox.confirm(
            "No Resend Record Exists For This Order ! Redirect To Resend Form ?",
            "Warning",
            {
              confirmButtonText: "OK",
              cancelButtonText: "Cancel",
              type: "warning",
            }
          )
            .then(() => {
              return router.push(`/Resend/${props.order_id}`);
            })
            .catch(() => {
              return router.push("/");
            });
        }
      })
      .catch((err) => console.log(err.message));
  }
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
    })
    .catch((err) => console.log(err.message));
}

onMounted(() => {
  checkOrderExists("returns", props.order_id);

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
      router.push("/");
    } else {
      modal.baseOrder = res.data[props.order_id];

      Object.values(modal.baseOrder.items).forEach((item) =>
        modal.addItem(fespItemSerializer(item))
      );
    }
  });
});
</script>
