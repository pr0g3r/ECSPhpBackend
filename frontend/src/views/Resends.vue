<template>
  <el-container>
    <el-header height="65px">
      <filter-set
        :filterSets="[
          'courier',
          'reason',
          'option',
          'room',
          'picker',
          'packer',
        ]"
        @url-args="(args) => main.buildRequest('resends', args)"
      />
    </el-header>

    <el-main>
      <el-table
        :data="main.orders"
        style="width: 100%"
        stripe
        @sort-change="setOrdering"
      >
        <el-table-column prop="order" label="Order" width="200" />
        <el-table-column prop="source" label="Source" width="100" />
        <el-table-column prop="courier" label="Courier" width="75" />
        <el-table-column prop="tracking_id" label="Tracking" width="175" />
        <el-table-column prop="name" label="Name" width="125" show-overflow-tooltip />
        <el-table-column prop="reason" label="Reason" width="150" />
        <el-table-column prop="option" label="Option" width="200" show-overflow-tooltip />
        <el-table-column prop="room" label="Room" width="75" />
        <el-table-column prop="picker" label="Picker" width="150" />
        <el-table-column prop="packer" label="Packer" width="150" />
        <el-table-column prop="created" label="Created" width="100" sortable="custom" />
        <el-table-column prop="dor" label="Dor" width="75" sortable="custom" />
        <el-table-column prop="notes" label="Notes" width="200" show-overflow-tooltip />
      </el-table>
    </el-main>
  </el-container>
</template>

<script setup>
/* Core Imports. */
import { onMounted } from 'vue'

/* Component Imports. */
import FilterSet from '../components/FilterSet.vue'

/* Library Imports. */
import { useMain } from '../store'

/* Util Imports. */
import request from '../utils/request'
import { setOrdering } from '../utils/generic';

/* Initialize Store. */
const main = useMain();

/* Reset current page on mount. */
onMounted(() => main.$reset());
</script>

<style scoped>
.el-header {
  padding: 1rem;
}
</style>
