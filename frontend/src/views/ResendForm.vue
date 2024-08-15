<template>
  <div>
    <el-header style="text-align: center">
      <h1>Resend Form</h1>
    </el-header>

    <el-divider></el-divider>
    <el-container>
      <el-main style="width: 50%">
        <el-divider>Resend Details</el-divider>
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

          <el-form-item prop="room" label="Room">
            <el-select filterable style="width: 100%" v-model="form.room">
              <el-option v-for="(option, index) in selects.room" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="picker" label="Picker">
            <el-select filterable style="width: 100%" v-model="form.picker">
              <el-option v-for="(option, index) in selects.warehouse" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="packer" label="Packer">
            <el-select filterable style="width: 100%" v-model="form.packer">
              <el-option v-for="(option, index) in selects.warehouse" :key="index" :value="option" />
            </el-select>
          </el-form-item>

          <el-form-item prop="courier" label="Courier">
            <el-select filterable style="width: 100%" v-model="form.courier">
              <el-option v-for="(option, index) in selects.courier" :key="index" :value="option" />
            </el-select>
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
        <el-divider>Resend Items</el-divider>
        <item-builder />
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
import { ElMessage } from "element-plus";

/* Util Imports. */
import rules from "../utils/rules";
import error from "../utils/error";
import request from "../utils/request";
import { fespRequest } from "../utils/fespUtils";
import {
  orderSerializer,
  resendSerializer,
  fespItemSerializer,
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
  room: [],
  warehouse: [],
  courier: [],
});
const form = reactive({
  order: "",
  original_order: props.order_id,
  created: new Date().toISOString().split("T")[0],
  reason: "",
  option: "",
  room: "",
  picker: "",
  packer: "",
  courier: "",
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
      const orderRecord = orderSerializer(modal.baseOrder);
      /* Set order_id for base order record to the
         original order_id not the variation. */
      orderRecord.order_id = form.original_order;

      /* Create base order record if it doesn't exist. */
      request
        .post("orders/", orderRecord)
        .then(() => createResend())
        .catch(() => createResend());

      /* Store the attachements for the order. */
      modal.handleAttachments(form.order, "Resend");

      router.push("/");
    } else {
      console.log("INVALID!");
    }
  });
}

/* Create pending record. */
function createResend() {
  request
    .post("pending_resends/", resendSerializer(form))
    .then(() => {
      /* Insert the order items. */
      modal.items.forEach((item) => {
        request
          .post("pending_items/", {
            order: form.order,
            sku: item.sku,
            title: item.title,
            qty: item.qty,
            shipping: item.shipping,
            price: item.price,
          })
          .catch((err) => error(err));
      });
    })
    .catch((err) => error(err));
}

/**
 * Convert integer into alphabetical character.
 *
 * @param {Number} c Number to converted into character.
 */
function nextChar(c) {
  var i = (parseInt(c, 36) + 1) % 36;
  return (!i * 10 + i).toString(36);
}

onMounted(() => {
  modal.$reset();

  /* Find the iteration of the base order. */
  request
    .get(`pending_resends/?search=${props.order_id}&page=1`)
    .then((res) => {
      let base = props.order_id;
      let iter = "a";

      if (res.data.results.length > 0) {
        let order = res.data.results.pop().order;

        if (order.match(/-[a-z]$/)) {
          form.order = order.match(/-[a-z]$/).pop();
          iter = nextChar(form.order.substr(1, 1));
        }
      }
      // Increment iteration.
      else if (props.order_id.match(/-[a-z]$/)) {
        base = props.order_id.match(/.+?(?=-[a-z]$)/);
        let currIter = props.order_id.match(/-[a-z]$/).pop();
        iter = nextChar(currIter.substr(1, 1));
      }

      form.order = `${base}-${iter}`;
    });

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
