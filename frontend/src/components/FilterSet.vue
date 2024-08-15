<template>
  <div style="border-bottom: thin solid #e5e5e5;">
    <el-row :gutter="10">
      <el-col :xs="2" :sm="2" :md="3" :lg="3" :xl="3">
        <div class="grid-content bg-purple">
          <el-input v-model="searchTerm" placeholder="Search">
            <template #prefix>
              <el-icon class="el-input__icon">
                <search />
              </el-icon>
            </template>
          </el-input>
        </div>
      </el-col>

      <el-col
        :xs="2"
        :sm="2"
        :md="3"
        :lg="3"
        :xl="3"
        v-for="(filter, index) in filterSets"
        :key="index"
      >
        <div class="grid-content bg-purple">
          <el-select filterable :placeholder="upperFirst(filter)" v-model="searchFilters[filter]">
            <el-option value>None</el-option>
            <el-option v-for="(option, index) in selects[filter]" :key="index" :value="option" />
          </el-select>
        </div>
      </el-col>
    </el-row>

    <el-row style="left: -0.66%;">
      <el-col :xs="2" :sm="2" :md="2" :lg="3" :xl="3">
        <el-pagination
          small
          layout="prev, ->, pager, ->, next"
          :current-page="main.currentPage"
          :page-count="main.maxPage"
          :pager-count="5"
          @current-change="(arg) => main.currentPage = arg"
        ></el-pagination>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
/* Component Imports. */
import { onMounted, onUnmounted, reactive, ref, watchEffect, watch } from "vue";

/* Library Imports. */
import { each, upperFirst } from "lodash";
import { useMain } from "../store";
import { Search } from "@element-plus/icons-vue";

/* Util Imports. */
import request from "../utils/request";

/* Component Properties. */
const props = defineProps({
  filterSets: {
    type: Array,
    required: true,
  },
});

/* Component Events. */
const emit = defineEmits(["url-args"]);

/* Initialize Store. */
const main = useMain();

/* Component Variables. */
const selects = reactive({});
const searchTerm = ref("");
const searchFilters = reactive({});

/* Reset page count on search input. */
watch([searchTerm, searchFilters], () => main.resetPage())

/* Rebuild search args and emit to parent on select option change. */
watchEffect(() => {
  let searchArg = searchTerm.value ? `&search=${searchTerm.value}` : "";
  let args = `${searchArg}`;
  each(searchFilters, (val, filter) => {
    if (val) {
      args += `&${filter}=${val}`;
    }
  });
  
  return emit("url-args", args);
});

const handler = (event) => {
  if (event.keyCode == 39) main.incPage();
  if (event.keyCode == 37) main.decPage();
};

onMounted(() => {
  request
    .get(`orders/order_form_options/${props.filterSets.join("/")}`)
    .then((res) => Object.assign(selects, res.data));

  /* Register key events. */
  window.addEventListener("keyup", handler);
});

/* Remove key events. */
onUnmounted(() => {
  window.removeEventListener("keyup", handler);
});
</script>
