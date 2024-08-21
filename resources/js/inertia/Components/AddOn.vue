<script setup>
const props = defineProps({
  quotePlanAddons: Array,
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="grid sm:grid-cols-1 mt-3">
      <dt class="font-medium mb-3">ADDONS</dt>
      <dd>
        <table style="width: 100%">
          <thead></thead>
          <tbody>
            <tr
              v-for="(addon, index) in quotePlanAddons"
              :key="addon"
              class="flex justify-between w-100"
            >
              <td style="width: 20%">{{ index + 1 }}</td>
              <td style="width: 20%">{{ addon.car_addon_text }}</td>
              <td style="width: 20%">{{ addon.car_addon_option_value }}</td>
              <td style="width: 20%">
                {{
                  addon.car_quote_request_addon_price == 0
                    ? 'Free'
                    : addon.car_quote_request_addon_price
                }}
              </td>
              <td>
                <input
                  v-if="addon.car_quote_request_addon_price"
                  type="checkbox"
                  checked
                  disabled
                  class="car-quote-ecom-non-free-plan-check"
                />
                <input
                  v-else
                  type="checkbox"
                  checked
                  disabled
                  class="car-quote-ecom-free-plan-check"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </dd>
    </div>
  </div>
</template>
