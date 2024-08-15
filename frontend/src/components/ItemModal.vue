<template>
  <el-dialog
    title="Item Modal"
    :model-value="visible"
    @close="emit('modal-close')"
  >
    <el-container>
      <el-main>
        <el-form
          id="item_form"
          label-width="125px"
          ref="formRef"
          :model="item"
          :rules="rules"
          @submit.prevent="handleForm()"
        >
          <el-form-item prop="sku" label="Sku">
            <el-input
              readonly
              v-model="item.sku"
            />
          </el-form-item>

          <el-form-item prop="title" label="Title">
            <el-input
              v-model="item.title"
            />
          </el-form-item>

          <el-form-item prop="qty" label="Qty">
            <el-input-number
              style="width: 50%"
              :min="1"
              v-model="item.qty"
            />
          </el-form-item>

          <el-form-item prop="price" label="Price">
            <el-input-number
              style="width: 50%"
              :precision="2"
              :min="0.00"
              :step="0.01"
              v-model="item.price"
            />
          </el-form-item>

          <el-form-item prop="shipping" label="Shipping">
            <el-input-number
              style="width: 50%"
              :precision="2"
              :min="0.00"
              :step="0.01"
              v-model="item.shipping"
            />
          </el-form-item>

          <el-form-item v-if="actions" prop="action_product" label="Action" >
            <el-select
              filterable
              v-model="item.action_product"
            >
              <el-option
                v-for="(option, index) in actions"
                :key="index"
                :value="option.name"
              />
            </el-select>

            <el-form-item prop="action_qty" label="Action Qty">
              <el-input-number
                :min="1"
                v-model="item.action_qty"
                />
            </el-form-item>
          </el-form-item>
        </el-form>
      </el-main>

      <el-footer class="ModalBtns">
        <el-button
          type="success"
          native-type="submit"
          form="item_form"
        >
          Submit
        </el-button>

        <el-button
          type="primary"
          @click="emit('modal-close')"
        >
          Cancel
        </el-button>
      </el-footer>
    </el-container>
  </el-dialog>
</template>

<script setup>
/* Core Imports. */
import { ref } from "vue"

/* Util Imports. */
import rules from '../utils/rules'

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
  item: {
    type: Object,
    required: true,
  },
  actions: {
    type: [Array, Boolean],
    required: false,
  }
})

/* Component Events. */
const emit = defineEmits(['modal-submit', 'modal-close'])

/* Component Variables. */
const formRef = ref('')

function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      emit('modal-submit', props.item)
    }
    else {
      console.log("INVALID!")
    }
  })
}
</script>
