<template>
  <div>
    <el-header>
      <el-row :gutter="20">
        <el-col :xs="1" :sm="1" :md="2" :lg="3" :xl="5">
          <div class="block">
            <el-date-picker
              v-model="dateStart"
              type="date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              placeholder="Pick a day"
              :shortcuts="shortcuts"
            ></el-date-picker>
          </div>
        </el-col>

        <el-col :xs="1" :sm="1" :md="2" :lg="3" :xl="5">
          <div class="block">
            <el-date-picker
              v-model="dateEnd"
              type="date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              placeholder="Pick a day"
              :shortcuts="shortcuts"
            ></el-date-picker>
          </div>
        </el-col>

        <el-col :xs="1" :sm="2" :md="3" :lg="3" :xl="5">
          <div class="grid-content bg-purple">
            <el-input placeholder="Search" v-model="orderSearch">
              <template #prefix>
                <el-icon class="el-input__icon">
                  <search />
                </el-icon>
              </template>
            </el-input>
          </div>
        </el-col>

        <el-col :xs="1" :sm="2" :md="3" :lg="3" :xl="5">
          <div class="grid-content bg-purple">
            <el-select filterable placeholder="Order Type" v-model="orderType">
              <el-option value="Resend" />
              <el-option value="Return" />
              <el-option value="Refund" />
            </el-select>
          </div>
        </el-col>

        <el-col :xs="8" :sm="2" :md="7" :lg="3" :xl="4">
          <div class="grid-content bg-purple">
            <el-button @click="createOrder">Create</el-button>
          </div>
        </el-col>
      </el-row>
    </el-header>

    <el-container>
      <el-main style="width: 25%">
        <el-divider>Resends</el-divider>
        <el-row>
          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.resends?.reasonsDaily"
              type="line"
              :options="resendStats.orders.options"
              :series="resendStats.orders.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.resends?.reasonsDaily"
              type="line"
              :options="resendStats.reasonsDaily.options"
              :series="resendStats.reasonsDaily.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.resends?.reasonsDaily"
              type="donut"
              :options="resendStats.countReasons.options"
              :series="resendStats.countReasons.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.resends?.reasonsDaily"
              width="100%"
              type="bar"
              :options="resendStats.warehouse.options"
              :series="resendStats.warehouse.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.resends?.reasonsDaily"
              width="100%"
              type="donut"
              :options="resendStats.rooms.options"
              :series="resendStats.rooms.series"
            />
          </el-col>
        </el-row>
      </el-main>

      <el-main style="width: 25%">
        <el-divider>Returns</el-divider>
        <el-row>
          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.returns?.reasonsDaily"
              type="line"
              :options="returnStats.orders.options"
              :series="returnStats.orders.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.returns?.reasonsDaily"
              width="100%"
              type="line"
              :options="returnStats.reasonsDaily.options"
              :series="returnStats.reasonsDaily.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.returns?.reasonsDaily"
              width="100%"
              type="donut"
              :options="returnStats.countReasons.options"
              :series="returnStats.countReasons.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.returns?.reasonsDaily"
              width="100%"
              type="donut"
              :options="returnStats.actionCustomers.options"
              :series="returnStats.actionCustomers.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.returns?.reasonsDaily"
              width="100%"
              type="donut"
              :options="returnStats.actionProducts.options"
              :series="returnStats.actionProducts.series"
            />
          </el-col>
        </el-row>
      </el-main>

      <el-main style="width: 25%">
        <el-divider>Refunds</el-divider>
        <el-row>
          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.refunds?.reasonsDaily"
              type="line"
              :options="refundStats.orders.options"
              :series="refundStats.orders.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.refunds?.reasonsDaily"
              width="100%"
              type="line"
              :options="refundStats.reasonsDaily.options"
              :series="refundStats.reasonsDaily.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.refunds?.reasonsDaily"
              width="100%"
              type="donut"
              :options="refundStats.countReasons.options"
              :series="refundStats.countReasons.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.refunds?.reasonsDaily"
              width="100%"
              type="line"
              :options="refundStats.amountDaily.options"
              :series="refundStats.amountDaily.series"
            />
          </el-col>

          <el-col v-loading="loading">
            <apex-chart
              v-if="orderStats.refunds?.reasonsDaily"
              width="100%"
              type="line"
              :options="refundStats.voidDaily.options"
              :series="refundStats.voidDaily.series"
            />
          </el-col>
        </el-row>
      </el-main>

      <el-main style="width: 25%">
        <el-divider>Out Of Stock</el-divider>
        <el-row>
          <el-col>
            <apex-chart
              v-if="orderStats.oos?.keys"
              type="donut"
              :options="oosStats.count.options"
              :series="oosStats.count.series"
            />

            <apex-chart
              v-if="orderStats.oos?.keys"
              width="100%"
              type="donut"
              :options="oosStats.total.options"
              :series="oosStats.total.series"
            />
          </el-col>
        </el-row>
      </el-main>
    </el-container>
  </div>
</template>

<script setup>
/* Component Imports. */
import ApexChart from "vue3-apexcharts";

/* Library Imports. */
import axios from "axios";
import { useRouter } from "vue-router";
import { Search } from "@element-plus/icons-vue";
import { computed, reactive, ref, watchEffect } from "vue";

/* Util Imports. */
import request from "../utils/request";
import { fespRequest } from "../utils/fespUtils";
import { ElMessage } from "element-plus";

const router = useRouter();
const shortcuts = [
  {
    text: "Today",
    value: new Date(),
  },
  {
    text: "Yesterday",
    value: () => {
      const date = new Date();
      date.setTime(date.getTime() - 3600 * 1000 * 24);
      return date;
    },
  },
  {
    text: "A week ago",
    value: () => {
      const date = new Date();
      date.setTime(date.getTime() - 3600 * 1000 * 24 * 7);
      return date;
    },
  },
  {
    text: "A month ago",
    value: () => {
      const start = new Date();
      start.setMonth(start.getMonth() - 1);
      return start;
    },
  },
  {
    text: "3 month ago",
    value: () => {
      const start = new Date();
      start.setMonth(start.getMonth() - 3);
      return start;
    },
  },
  {
    text: "6 month ago",
    value: () => {
      const start = new Date();
      start.setMonth(start.getMonth() - 6);
      return start;
    },
  },
  {
    text: "This year",
    value: () => new Date(new Date().getFullYear(), 0),
  },
];

const dateEnd = ref(new Date().toISOString().split("T")[0]);
let date = new Date(dateEnd.value);
date.setDate(date.getDate() - 7);
const dateStart = ref(date.toISOString().split("T")[0]);
const dateRange = computed(() => {
  if (orderStats.oos?.range) {
    return orderStats.oos.range;
  }
});
const orderStats = reactive({
  resends: {},
  returns: {},
  refunds: {},
  oos: {},
});
const orderSearch = ref("");
const orderType = ref("");
const loading = ref(false);

const resendStats = computed(() => {
  return {
    orders: OrderChartType(
      "Count Of Resend Orders By Day",
      "resends",
      "created",
      "order_total",
      "orders"
    ),
    reasonsDaily: orderReasonDaily("resends"),
    countReasons: OrderChartType(
      "Count Of Resend Reasons",
      "resends",
      "reason",
      "order_total",
      "reasons",
      true
    ),
    warehouse: {
      options: chartOptions(
        "Count Of Resend Orders By Warehouse Staff",
        Object.values(orderStats.resends.packers).map((row) => row.warehouse)
      ),
      series: [
        {
          name: "Packer",
          data: Object.values(orderStats.resends.packers).map(
            (row) => row.order_total
          ),
        },
        {
          name: "Picker",
          data: Object.values(orderStats.resends.pickers).map(
            (row) => row.order_total
          ),
        },
      ],
    },
    rooms: OrderChartType(
      "Count Of Resend Orders By Room",
      "resends",
      "room",
      "order_total",
      "rooms",
      true
    ),
  };
});

const returnStats = computed(() => {
  return {
    orders: OrderChartType(
      "Count Of Return Orders By Day",
      "returns",
      "created",
      "order_total",
      "orders"
    ),
    countReasons: OrderChartType(
      "Count Of Return Reasons",
      "returns",
      "reason",
      "order_total",
      "reasons",
      true
    ),
    actionCustomers: OrderChartType(
      "Count of Return Customer Actions",
      "returns",
      "action",
      "order_total",
      "actionCustomers",
      true
    ),
    actionProducts: OrderChartType(
      "Count of Return Product Actions",
      "returns",
      "action",
      "order_total",
      "actionProducts",
      true
    ),
    reasonsDaily: orderReasonDaily("returns"),
  };
});

const refundStats = computed(() => {
  return {
    orders: OrderChartType(
      "Count Of Refund Orders By Day",
      "refunds",
      "created",
      "order_total",
      "orders"
    ),
    countReasons: OrderChartType(
      "Count Of Refund Reasons",
      "refunds",
      "reason",
      "order_total",
      "reasons",
      true
    ),
    reasonsDaily: orderReasonDaily("refunds"),
    amountDaily: OrderChartType(
      "Refund Amount Daily",
      "refunds",
      "created",
      "amount_total",
      "amountDaily",
      false,
      "Amount"
    ),
    voidDaily: OrderChartType(
      "Count Of Void Refunds By Day",
      "refunds",
      "created",
      "order_total",
      "voidDaily"
    ),
  };
});

const oosStats = computed(() => {
  return {
    count: OrderChartType(
      "Count Of Out Of Stock Held Orders",
      "oos",
      "key",
      "count",
      "keys",
      true
    ),
    total: OrderChartType(
      "Total Of Out Of Stock Held Orders",
      "oos",
      "key",
      "total",
      "keys",
      true
    ),
  };
});

function chartOptions(title, labels) {
  let xaxis = labels ? {} : { categories: dateRange.value };

  return {
    title: {
      text: title,
      align: "left",
    },
    stroke: {
      width: 3,
      curve: "smooth",
    },
    yaxis: {
      min: 0,
    },
    xaxis,
    grid: {
      row: {
        colors: ["#f3f3f3", "transparent"],
        opacity: 0.5,
      },
    },
    legend: {
      show: true,
      position: "bottom",
    },
    plotOptions: {
      pie: {
        donut: {
          labels: {
            show: true,
            total: {
              show: true,
            },
          },
        },
      },
      bar: {
        borderRadius: 4,
        horizontal: true,
      },
    },
    labels: labels,
  };
}

function OrderChartType(
  title,
  orderType,
  targetColumn,
  targetValue,
  statType,
  pie = false,
  seriesType
) {
  let series = [];
  if (pie) {
    series = Object.values(orderStats[orderType][statType]).map(
      (row) => row[targetValue]
    );
  } else {
    series = [
      {
        name: seriesType ? seriesType : "Orders",
        data: Object.values(orderStats[orderType][statType]).map(
          (row) => row[targetValue]
        ),
      },
    ];
  }
  let labels = Object.values(orderStats[orderType][statType]).map(
    (row) => row[targetColumn]
  );
  let options = chartOptions(title, labels);

  return {
    options,
    series,
  };
}

function orderReasonDaily(orderType) {
  let groupedByDate = {};
  Object.values(orderStats[orderType].reasonsDaily).forEach((row) => {
    if (!groupedByDate[row.reason]) groupedByDate[row.reason] = {};
    groupedByDate[row.reason][row.created] = row.order_total;
  });

  // Fix apexcharts terrible key value mapping behavior
  let formattedDateCount = {};
  Object.entries(groupedByDate).forEach((row) => {
    Object.values(dateRange.value).forEach((key, index) => {
      let reason = row[0];
      let order_totals = row[1];

      if (!formattedDateCount[reason]) formattedDateCount[reason] = {};
      if (order_totals[key] === undefined) {
        formattedDateCount[reason][index] = 0;
      } else {
        formattedDateCount[reason][index] = order_totals[key];
      }
    });
  });

  let series = [];
  Object.entries(formattedDateCount).forEach((values) => {
    series.push({
      name: values[0],
      data: Object.values(values[1]),
    });
  });

  let upperOrderType =
    orderType[0].toUpperCase() + orderType.slice(1).toLowerCase();

  return {
    options: chartOptions(`${upperOrderType} Reason Count By Day`),
    series,
  };
}

function createOrder() {
  if (orderSearch.value && orderType.value) {
    return router.push(`/${orderType.value}/${orderSearch.value}`);
  }

  ElMessage({
    message:
      "Please enter an order number and select a type to create a record.",
    type: "warning",
    duration: 10 * 1000,
  });
}

watchEffect(() => {
  /* REVIEW: Produce dashboard router to handle these calls for simplicity. */
  loading.value = true;
  Promise.all([
    // Out of stock sale stats
    fespRequest("StockController", "outOfStockSales", [
      dateStart.value,
      dateEnd.value,
    ]),

    // Resend requests
    request.get(`resends/get_order_count/${dateStart.value}/${dateEnd.value}`),
    request.get(`resends/get_reason_count/${dateStart.value}/${dateEnd.value}`),
    request.get(`resends/get_reason_daily/${dateStart.value}/${dateEnd.value}`),
    request.get(
      `resends/get_warehouse_count/picker/${dateStart.value}/${dateEnd.value}`
    ),
    request.get(
      `resends/get_warehouse_count/packer/${dateStart.value}/${dateEnd.value}`
    ),
    request.get(`resends/get_room_count/${dateStart.value}/${dateEnd.value}`),

    // Return requests
    request.get(`returns/get_order_count/${dateStart.value}/${dateEnd.value}`),
    request.get(`returns/get_reason_count/${dateStart.value}/${dateEnd.value}`),
    request.get(`returns/get_reason_daily/${dateStart.value}/${dateEnd.value}`),
    request.get(
      `returns/action_customer_count/${dateStart.value}/${dateEnd.value}`
    ),
    request.get(
      `returns/action_product_count/${dateStart.value}/${dateEnd.value}`
    ),

    // Refund requests
    request.get(`refunds/get_order_count/${dateStart.value}/${dateEnd.value}`),
    request.get(`refunds/get_reason_count/${dateStart.value}/${dateEnd.value}`),
    request.get(`refunds/get_reason_daily/${dateStart.value}/${dateEnd.value}`),
    request.get(`refunds/get_amount_daily/${dateStart.value}/${dateEnd.value}`),
    request.get(`refunds/get_void_daily/${dateStart.value}/${dateEnd.value}`),
  ]).then(
    axios.spread(
      (
        oos,

        resendOrders,
        resendReasons,
        resendReasonsDaily,
        resendPickers,
        resendPackers,
        resendRooms,

        returnOrders,
        returnReasons,
        returnReasonsDaily,
        returnActionCustomers,
        returnActionProducts,

        refundOrders,
        refundReasons,
        refundReasonsDaily,
        refundAmountDaily,
        refundVoidDaily
      ) => {
        orderStats.oos = oos.data;

        orderStats.resends.orders = resendOrders.data;
        orderStats.resends.reasons = resendReasons.data;
        orderStats.resends.reasonsDaily = resendReasonsDaily.data;
        orderStats.resends.pickers = resendPickers.data;
        orderStats.resends.packers = resendPackers.data;
        orderStats.resends.rooms = resendRooms.data;

        orderStats.returns.orders = returnOrders.data;
        orderStats.returns.reasons = returnReasons.data;
        orderStats.returns.reasonsDaily = returnReasonsDaily.data;
        orderStats.returns.actionCustomers = returnActionCustomers.data;
        orderStats.returns.actionProducts = returnActionProducts.data;

        orderStats.refunds.orders = refundOrders.data;
        orderStats.refunds.reasons = refundReasons.data;
        orderStats.refunds.reasonsDaily = refundReasonsDaily.data;
        orderStats.refunds.amountDaily = refundAmountDaily.data;
        orderStats.refunds.voidDaily = refundVoidDaily.data;
        
        loading.value = false;
      }
    )
  );
});
</script>

<style scoped>
.el-header {
  margin-top: 10px;
  height: 40px;
}
</style>
