<template>
  <div>

    <div v-if="!isLogged">
      <el-container>
      <el-main>
        <div>
          <el-form
            id="form"
            style="max-width: 75%"
            label-width="150px"
            ref="formRef"
            :model="form"
            :rules="rules"
            @submit.prevent="handleForm"
          >
            <el-form-item prop="username" label="Username">
              <el-input v-model="form.username" />
            </el-form-item>

            <el-form-item prop="password" label="Password">
              <el-input type="password" v-model="form.password" />
            </el-form-item>

            <el-form-item>
              <el-button style="width: 100%" type="primary" native-type="submit" form="form">Login</el-button>
            </el-form-item>
          </el-form>
        </div>
      </el-main>
      </el-container>
    </div>
    

    <div v-if="isLogged">
      <el-container>
        <el-main>
          <div>
            <el-form
              style="max-width: 75%"
              label-width="150px"
            >
              <el-form-item label="User">
              <el-input readonly :placeholder="getToken('Ecs_User')"></el-input>
            </el-form-item>

            <el-form-item>
              <el-button style="width: 100%" type="danger" @click="logout">Logout</el-button>
            </el-form-item>
            </el-form>        
          </div>
        </el-main>
      </el-container>
    </div>
  </div>
</template>

<script setup>
/* Core Imports. */
import { computed, reactive, ref } from "vue";

/* Library Imports. */
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";

/* Util Imports. */
import rules from "../utils/rules";
import request from "../utils/request";
import { getToken, loginUser, destorySession } from "../utils/auth"

/* Initialize Router. */
const router = useRouter();

/* View Variables. */
const formRef = ref("")
const form = reactive({
  username: "",
  password: "",
});
const isLogged = computed(() => getToken("AccessToken"));

async function handleForm() {
  formRef.value.validate((valid) => {
    if (valid) {
      // Sends a POST to the php backend
      request
        .post("api/token/", {
          username: form.username,
          password: form.password,
        })
        .then((res) => loginUser(res.data))
        .catch(() => {
          ElMessage({
            message: "No User With Matching Credentials.",
            type: "warning",
            duration: 7 * 1000,
          });

          form.username = "";
          form.password = "";
        })
        .finally(() => {
          // If user was redirected, push them back
          if (sessionStorage.getItem("ECS_PRE_REDIRECT") && sessionStorage.getItem("ECS_PRE_REDIRECT") !== "/Login") {
            router.push(sessionStorage.getItem("ECS_PRE_REDIRECT"));
          }

          router.push("/");
        })
    } else {
      console.log("INVALID!");
    }
  });
}

function logout() {
  destorySession();

  return router.go(router.currentRoute);
}
</script>

<style scoped>
.el-container {
  position: absolute;
  top: 10%;
  width: 99%;
  height: 60%;
}

.el-main {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
