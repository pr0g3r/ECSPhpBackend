<template>
  <el-container v-show="view.collection">
    <el-container>
      <el-header>
        <el-row :gutter="10">
          <el-col :xs="3" :sm="3" :md="3" :lg="3" :xl="3">
            <el-select
              placeholder="Courier"
              filterable
              v-model="conditions.courier"
              @change="collectClaims"
            >
              <el-option value>None</el-option>
              <el-option
                v-for="(option, index) in selects.courier"
                :key="index"
                :value="option.name"
              />
            </el-select>
          </el-col>

          <el-col :xs="3" :sm="3" :md="3" :lg="3" :xl="3">
            <el-select
              placeholder="Option"
              filterable
              v-model="conditions.option"
              @change="collectClaims"
            >
              <el-option value>None</el-option>
              <el-option
                v-for="(option, index) in selects.option"
                :key="index"
                :value="option.name"
              />
            </el-select>
          </el-col>

          <el-col :xs="3" :sm="3" :md="3" :lg="3" :xl="3">
            <el-button type="success" @click="createForm">Create</el-button>
          </el-col>
        </el-row>
      </el-header>

      <el-row style="height: 50%">
        <el-divider>Collected: {{claims.collected.length}}</el-divider>
        <el-table
          v-loading="loading.collection"
          :data="filterCollectedData"
          style="width: 100%"
          height="250"
          stripe
        >
          <el-table-column prop="order" label="Order" />
          <el-table-column prop="courier" label="Courier" />
          <el-table-column prop="reason" label="Reason" />
          <el-table-column prop="created" label="Created" />
          <el-table-column prop="user" label="User" />
          <el-table-column align="right">
            <template #header>
              <el-input v-model="filters.collected" size="small" placeholder="Type to search" />
            </template>
          </el-table-column>
        </el-table>
      </el-row>

      <el-main>
        <el-row style="height: 25%">
          <el-divider>Forms: {{claims.forms.length}}</el-divider>
          <el-table
            :data="claims.forms"
            style="width: 100%"
            height="250"
            :row-class-name="formTableClass"
          >
            <el-table-column prop="reference" label="Reference" />
            <el-table-column prop="courier" label="Courier" />
            <el-table-column prop="created" label="Created" />
            <el-table-column prop="claim_count" label="Claims" />
            <el-table-column prop="status" label="Status" />
            <el-table-column prop="expected_payout" label="Expected" />
            <el-table-column prop="actual_payout" label="Actual" />
            <el-table-column prop="user" label="User" />

            <el-table-column fixed="right" label="Operations" width="400">
              <template #default="scope">
                <el-button
                  type="primary"
                  size="small"
                  round
                  @click="editForm(scope.$index, scope.row)"
                >Edit</el-button>

                <el-button
                  size="small"
                  type="primary"
                  plain
                  round
                  @click="exportForm(scope.row)"
                >Form</el-button>

                <el-button
                  size="small"
                  type="primary"
                  plain
                  round
                  @click="exportAttachments(scope.row)"
                >Attachments</el-button>

                <el-button
                  v-if="scope.row.status === null"
                  type="success"
                  size="small"
                  round
                  @click="responseForm(scope.$index, scope.row)"
                >Respond</el-button>

                <el-button
                  v-if="scope.row.status === false"
                  type="warning"
                  size="small"
                  round
                  @click="disputeClaim(scope.$index, scope.row)"
                >Dispute</el-button>

                <el-button
                  v-if="scope.row.status === false"
                  type="warning"
                  size="small"
                  round
                  @click="disputeResponse(scope.$index, scope.row)"
                >Dispute Resp</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-row>
      </el-main>
    </el-container>

    <el-container v-if="view.form">TEST</el-container>
  </el-container>
</template>

<script setup>
/* Core Imports. */
import { onMounted, reactive, computed } from "vue";

/* Library Imports. */
import axios from "axios";
import { ElMessage, ElMessageBox } from "element-plus";
import { now } from "lodash";

/* Util Imports. */
import request from "../utils/request";
import { fespRequest } from "../utils/fespUtils";
import { exportCsv } from "../utils/generic";
import error from "../utils/error";

/* View Variables. */
const selects = reactive({
  courier: [],
  option: [],
});
const conditions = reactive({
  courier: "",
  option: "",
});
const claims = reactive({
  collected: [],
  forms: [],
});
const filters = reactive({
  collected: "",
});
const view = reactive({
  collection: true,
  form: false,
});
const loading = reactive({
  collection: false,
  forms: false,
});

/* Filter collected records using order field. */
const filterCollectedData = computed(() =>
  claims.collected.filter(
    (data) =>
      !filters.collected ||
      data.order.toLowerCase().includes(filters.collected.toLowerCase())
  )
);

/* Collect a set of claims that match the conditions the user has selected. */
function collectClaims() {
  loading.collection = true;
  request
    .post(`claims/claimable/`, {
      courier: conditions.courier,
      option: conditions.option,
    })
    .then((res) => {
      claims.collected = res.data;
      loading.collection = false;
    });
}

function collectForms() {
  loading.forms = true;
  request.get("claim_forms/").then((res) => {
    claims.forms = res.data.results;
    loading.forms = false;
  });
}

/**
 * Update the frontend and backend reference number for the targeted row,
 * will also update the name of the saved copy of the claims csv that is
 * stored on the server.
 *
 * @param {Number} index Index of form record in claims.forms.
 * @param {Object} row Target object from the table row.
 */
function editForm(index, row) {
  ElMessageBox.prompt("Enter New Reference Number", "Tip", {
    confirmButtonText: "OK",
    cancelButtonText: "Cancel",
  })
    .then((reference) => {
      if (reference.value) {
        updateForm(
          row.id,
          index,
          claims.forms[index].reference,
          reference.value
        );
      }
    })
    .catch(() => {
      ElMessage({
        message: "Canceled reference number update.",
        type: "info",
        duration: 2.5 * 1000,
      });
      return;
    });
}

function updateForm(id, index, oldReference, newReference) {
  request
    .patch(`claim_forms/${id}/`, {
      reference: newReference,
    })
    .then(() => {
      // Update form name on serverside.
      request.post(import.meta.env.VITE_BASE_ROOT + "ECS_FILES.php", {
        action: { renameForm: true },
        oldReference,
        newReference,
      });
    })
    .catch((err) => error(err));

  claims.forms[index].reference = newReference;
}

/**
 * Retrieve the claims csv for the target row in the forms table.
 *
 * @param {Object} row Target row form forms table.
 */
function exportForm(row) {
  return window.open(
    import.meta.env.VITE_BASE_ROOT +
      "ECS_DOWNLOAD.php?path=" +
      import.meta.env.VITE_ASSET_PATH +
      `assets/claims/${row.reference}.csv`,
    "_blank"
  );
}

/**
 * Retrieve the a zip folder of attachments for a submitted form.
 *
 * @param {Object} row Target row form forms table.
 */
function exportAttachments(row) {
  return window.open(
    import.meta.env.VITE_BASE_ROOT +
      "ECS_DOWNLOAD.php?path=" +
      import.meta.env.VITE_ASSET_PATH +
      `assets/order_attachments/${row.reference}_attachments.zip`,
    "_blank"
  );
}

function responseForm(index, row) {
  // Prompt user for a response file.
  // Check that the name of the file matches the reference number.

  let response = document.createElement("input");
  response.type = "file";
  response.click();

  response.onchange = (event) => {
    let file = event.target.files[0];

    if (
      file.type === "text/csv" ||
      file.type ===
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    ) {
      let fileRef = file.name.slice(0, file.name.lastIndexOf("."));
      let fileExt = file.name.substring(file.name.lastIndexOf(".") + 1);

      if (fileRef !== row.reference) {
        ElMessageBox({
          title: "Message",
          message:
            "Override reference number ? this will update the form to use the reference number provided in uploaded csv name.",
          showCancelButton: true,
          confirmButtonText: "OK",
          cancelButtonText: "Cancel",
        })
          // Confirmed request
          .then((action) => {
            if (action === "confirm") {
              // Update the reference on backend.
              updateForm(row.id, index, row.reference, fileRef);
              row.reference = fileRef;
            }
          })
          // Cancelled request
          .catch(() => {
            ElMessage({
              message: "Canceled response request.",
              type: "info",
              duration: 5 * 1000,
            });
            return;
          });
      }

      loading.forms = true;
      // Save copy of response server side
      let form = new FormData();
      form.append(row.reference, file, `${row.reference}.${fileExt}`);
      request
        .post(import.meta.env.VITE_BASE_ROOT + "ECS_FILES.php", form, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
          params: {
            action: "storeFile",
            location: "responses",
          },
        })
        .then(() => {
          fespRequest("EcsController", "courierResponseHandler", [
            row.courier,
            row.reference,
            `C:/inetpub/wwwroot/FESP-REFACTOR/FespMVC/ECS/assets/responses/${row.reference}.${fileExt}`,
          ]).then((res) => {
            let ids = Object.values(res.data.records).map((row) => row.order);
            request
              .post("claims/form_response/", {
                ids: ids,
                form: row.id,
                reference: row.reference,
                actual_payout: res.data.actual_payout,
                response: res.data.records,
              })
              .then(() => {
                collectForms();
                loading.forms = false;
              })
              .catch((err) => error(err));
          });
        });
    } else {
      console.log("Wrong Type!");
    }
  };
}

/* Create a form using the oldest 100 records in the collected results. */
async function createForm() {
  if (!conditions.courier || !conditions.option) {
    ElMessage({
      message: "Please select a courier and a option to create a form.",
      type: "warning",
      duration: 2.5 * 1000,
    });
    return;
  }

  // Get the first 100 entries in the collected claims
  const claims_length =
    claims.collected.length >= 100 ? 100 : claims.collected.length;
  const formIds = claims.collected.slice(0, claims_length);
  const response = await fespRequest("EcsController", "claimsForm", [
    conditions.courier,
    conditions.option,
    formIds,
  ]);

  // Warn user that not all records could be handled, as some were missing
  // from fesp, this can be due to the records being archived.
  if (response.data.records.length != formIds.length) {
    const missing = formIds.length - response.data.records.length;
    ElMessage({
      message: `There were ${missing} records not included in the form, this is due to them being missing from fesp.`,
      type: "info",
      duration: 5 * 1000,
    });
  }

  // Prompt user to save csv response.
  const csv = exportCsv(response.data.records);
  // Register form on backend.
  processForm(
    response.data.validids,
    response.data.expected_payout,
    response.data.totals,
    response.data.trackingids,
    csv
  );
}

/**
 * Creates a form record on the backend and stores a copy of
 * the form on FESP located at /FESP-REFACTOR/FespMVC/ECS/assets/claims
 *
 * @param {Array} ids The collected ids matching the conditions
 * the user selected.
 * @param {String} csv Comma separated string of the claims to be
 * sent to the courier.
 */
async function processForm(ids, expected, totals, tracking, csv) {
  // Can only continue if the user enters a reference number.
  let ref = await ElMessageBox.prompt("Reference Number", "Tip", {
    confirmButtonText: "OK",
    cancelButtonText: "Cancel",
  });
  if (!ref.value) {
    ElMessage({
      message:
        "Form reference saved as timestamp,\n please enter the reference number at a later date.",
      type: "info",
      duration: 7 * 1000,
    });
    ref.value = now();
  }

  // Create form record.
  request
    .post("claim_forms/", {
      reference: ref.value,
      courier: conditions.courier,
      claim_count: ids.length,
      expected_payout: expected,
      created: new Date().toISOString().split("T")[0],
      user: localStorage.getItem("Ecs_User"),
    })
    .then((res) => {
      // Register orderids to the created form.
      request
        .post("claims/form_orders/", {
          form: res.data.id,
          ids: ids,
          totals: totals,
        })
        .then(() => {
          // Update claims and forms table data.
          conditions.courier = "";
          conditions.option = "";
          collectClaims();
          collectForms();
        })
        .catch((err) => error(err));
    })
    .catch((err) => error(err));

  // Save copy serverside
  request.post(import.meta.env.VITE_BASE_ROOT + "ECS_FILES.php", {
    action: { store: true },
    location: "claims",
    file: csv,
    name: ref.value,
    ext: "csv",
  });

  // For certain types of form will need to gather the images required for dor.
  request
    .post(import.meta.env.VITE_BASE_ROOT + "ECS_FILES.php", {
      action: { getFormAttachments: true },
      reference: ref.value,
      attachment_ids: tracking,
    })
    .then((res) => {
      // Send this path to the php download script which will allow us to pass the user files.
      const downloadPath = res.data;
      window.open(
        import.meta.env.VITE_BASE_ROOT +
          "ECS_DOWNLOAD.php?path=" +
          downloadPath,
        "_blank"
      );
    });
}

// Return get the disputed records for the targeted form,
// produce a dispute form in the format requried by the courier
// and filter the records from the original response using the
// retrieve disputed records.
function disputeClaim(index, row) {
  request.get(`claims/disputed_claims/${row.id}`).then((disputed) => {
    fespRequest("EcsController", "courierDisputeHandler", [
      row.courier,
      row.reference,
      disputed.data,
    ]).then((res) => {
      exportCsv(res.data);
    });
  });
}

function disputeResponse(index, row) {
  responseForm(index, row);
}

const formTableClass = ({ row }) => {
  switch (row.status) {
    case null:
      return "pending-row";
    case false:
      return "dispute-row";
    case true:
      return "complete-row";
  }
};

onMounted(() => {
  collectClaims();
  collectForms();
  axios
    .all([
      request.get("couriers?claimable=1"),
      request.get("options?claimable=1"),
    ])
    .then(
      axios.spread((courier, option) => {
        selects.courier = courier.data.results;
        selects.option = option.data.results;
      })
    );
});
</script>

<style scoped>
.el-header {
  margin-top: 10px;
  height: 40px;
}
</style>
