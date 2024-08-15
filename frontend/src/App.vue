<template>
  <div>
    <el-menu
      mode="horizontal"
      class="el-menu-demo"
      style="border-bottom: thin solid #e5e4e2"
      router
    >
    <el-menu-item index="1" route="/" class="MenuItem">
      <i class="fas fa-home"></i>
      Dashboard
    </el-menu-item>
    
    <el-menu-item index="2" route="/Pending" class="MenuItem">
      <i class="fas fa-ellipsis-h"></i>
      Pending
    </el-menu-item>

    <el-menu-item index="3" route="/Resends" class="MenuItem">
      <i class="fas fa-sync-alt"></i>
      Resends
    </el-menu-item>

    <el-menu-item index="4" route="/Returns" class="MenuItem">
      <i class="far fa-arrow-alt-circle-left"></i>
      Returns
    </el-menu-item>

    <el-menu-item index="5" route="/Refunds" class="MenuItem">
      <i class="fas fa-money-bill-wave-alt"></i>
      Refunds
    </el-menu-item>

    <el-menu-item index="6" route="/Claims" class="MenuItem">
      <i class="fas fa-file-csv"></i>
      Claims
    </el-menu-item>

    <el-menu-item index="7" route="/Admin" class="MenuItem">
      <i class="fas fa-user-shield"></i>
      Admin
    </el-menu-item>

    <el-menu-item index="8" route="/Login" class="MenuItem">
      <i class="fas fa-user-alt"></i>
      Login
    </el-menu-item>
    </el-menu>
    <router-view />
  </div>
</template>

<style scoped>
@import url(//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css);

.MenuItem {
  column-gap: 10px;
}
</style>
