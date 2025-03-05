<script setup>
import { decimal } from "@vuelidate/validators";
import { defineProps } from "vue";
import { CountTo } from "vue3-count-to";

defineProps({
  crmWidgets: Array, // Accept crmWidgets as a prop from index.vue
});
</script>

<template>
  <BCard no-body class=" crm-widget rounded-0">
    <BCardBody class="p-0">
      <BRow class="row-cols-xxl-5 row-cols-md-3 row-cols-1 g-0">
        <BCol v-for="(item, index) in crmWidgets" :key="index">
          <div class="py-4 px-3">
            <h5 class="text-muted text-uppercase fs-13">
              {{ item.label }}
              <i :class="`${item.badge} fs-18 float-end align-middle`"></i>
            </h5>
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i :class="`${item.icon} display-6 text-muted`"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h2 class="mb-0">
                  {{ item.prefix }}
                  <CountTo :startVal="0" :endVal="item.counter" :duration="5000" :decimals="item.decimals"/>
                  {{ item.suffix }}
                </h2>
              </div>
            </div>
          </div>
        </BCol>
      </BRow>
    </BCardBody>
  </BCard>
</template>
