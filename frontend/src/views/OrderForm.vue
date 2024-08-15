<template>
  <div>
    <el-form
      id="form"
      label-width="125px"
      ref="formRef"
      :model="options.order"
      :rules="rules"
      @submit.prevent="handleForm"
    >
      <el-container>
        <el-main style="max-width: 25%">
          <el-divider>Contact</el-divider>
          <el-form-item prop="buyer" label="Delivery Name">
            <el-input v-model="options.order.buyer"/>
          </el-form-item>

          <el-form-item prop="phone" label="Phone No">
            <el-input v-model="options.order.phone" type="tel" />
          </el-form-item>

          <el-form-item prop="email" label="Email">
            <el-input v-model="options.order.email" type="email" />
          </el-form-item>

          <el-divider>Address</el-divider>
          <el-form-item prop="address1" label="Address 1">
            <el-input v-model="options.order.shipping.address1" />
          </el-form-item>

          <el-form-item prop="address2" label="Address 2">
            <el-input v-model="options.order.shipping.address2" />
          </el-form-item>

          <el-form-item prop="city" label="City">
            <el-input v-model="options.order.shipping.city" />
          </el-form-item>

          <el-form-item prop="county" label="County">
            <el-input v-model="options.order.shipping.county" />
          </el-form-item>

          <el-form-item prop="country" label="Country">
            <el-input v-model="options.order.shipping.countryCode" />
          </el-form-item>

          <el-form-item prop="postcode" label="Postcode">
            <el-input v-model="options.order.shipping.postCode" />
          </el-form-item>

          <el-divider>Miscellaneous</el-divider>
          <el-form-item prop="noOfLabels" label="No. Of Labels">
            <el-input-number style="width: 66%" v-model="options.order.parcelCount" :min="0" />
          </el-form-item>

          <el-form-item prop="weight" label="Weight">
            <el-input-number style="width: 66%" :precision="2" :min="0.00" :step="0.01" v-model="options.order.weight" />
          </el-form-item>

          <el-form-item prop="length" label="Length">
            <el-input-number style="width: 66%" :precision="2" :min="0.00" :step="0.01" v-model="options.order.length" />
          </el-form-item>
        </el-main>


        <el-main style="max-width: 25%">
          <el-divider>Order Details</el-divider>
          <el-form-item label="Order Number">
            <el-input readonly placeholder="AUTO-GENERATE" />
          </el-form-item>

          <el-form-item prop="courier" label="Courier">
            <el-select style="width: 100%" filterable v-model="options.order.courier">
              <el-option
                v-for="(option, index) in options.couriers"
                :key="index"
                :value="option.name"
              />
            </el-select>
          </el-form-item>

          <el-form-item prop="channel" label="Channel">
            <el-select style="width: 100%" filterable v-model="options.order.channel">
              <el-option
                v-for="(option, index) in options.channels"
                :key="index"
                :value="option"
              />
            </el-select>
          </el-form-item>

          <el-form-item prop="notes" label="Notes">
            <el-input type="textarea" placeholder="Notes" :rows="5" v-model="options.order.message" />
          </el-form-item>
        </el-main>

        <el-main style="max-width: 50%">
          <el-divider>Items</el-divider>
          <item-builder />
        </el-main>
      </el-container>
    </el-form>

    <el-divider></el-divider>
    <el-footer class="ModalBtns">
      <el-button type="success" native-type="submit" form="form">Submit</el-button>
    </el-footer>
  </div>
</template>

<script setup>
/* Core Imports. */
import { onMounted, reactive, ref } from "vue";

/* Component Imports. */
import ItemBuilder from "../components/ItemBuilder.vue";

/* Library Imports. */
import axios from "axios";
import { useModal } from "../store";
import { useRouter } from "vue-router"
import { ElMessage } from "element-plus"

/* Util Imports. */
import rules from "../utils/rules";
import { fespCreateOrder, fespRequest } from "../utils/fespUtils";
import { fespItemSerializer } from "../utils/serializers";

/* Define Properties. */
const props = defineProps({
  order_id: {
    type: String,
  },
})

/* Initialize Router. */
const router = useRouter();

/* Initialize Store. */
const modal = useModal();

/* Define Variables. */
const formRef = ref("")
const options = reactive({
  couriers: [],
  order: {
    shipping: {},
  },
  channels: ["elixir", "prosalt", "floorworld"],
});

function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      options.order.items = modal.items;
      
      fespRequest("OrderContoller", "serializeFespOrder", [ options.order ])
        .then((res) => fespCreateOrder(res.data));
    }
    else {
      console.log("INVALID!");
      ElMessage({
        message: 'Unable to create order record !',
        type: 'error',
        duration: 5 * 1000,
      })
    }
  })
}

onMounted(() => {
  modal.$reset();

  if (props.order_id) {
    fespRequest("OrderController", "getOrderContent", [ props.order_id, true])
      .then((res) => {
        if (res.data) {
          console.log(res.data);
          options.order = res.data[props.order_id];

          Object
            .values(options.order.items)
            .forEach((item) => modal.addItem(fespItemSerializer(item)));
        }
      })
  }

  axios
    .get(import.meta.env.VITE_PHP_API_URL + "couriers")
    .then((res) => {
      options.couriers = res.data.results;
    })
})
</script>

<style scoped>

</style>
