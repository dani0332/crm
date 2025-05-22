<script setup>
const page = usePage();
const paymentTooltipEnum = page.props.paymentTooltipEnum;
</script>

<template>
  <thead class="vue3-easy-data-table__header">
    <tr>
      <th class="relative group text-center">
        <span class="border-b border-dotted">Payment No</span>
        <div
          class="absolute text-left hidden group-hover:block transform transition-transform z-40 h-fit _popoverContent_1wc81_3 top-full bottom-0 _popoverBottom_1wc81_14 left-1/2 right-full -translate-x-1/2 max-w-xs"
        >
          <div class="dark">
            <div
              class="x-popover-container block w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border border-gray-200 dark:border-gray-800 p-2 text-white text-sm w-max max-w-xs"
            >
              <span data-v-d0063695="" class="custom-tooltip-content">
                {{ paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_NO }}
              </span>
            </div>
          </div>
        </div>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Payment Ref ID</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_REF_ID
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Collection Date</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTION_DATE
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Due Date</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_DUE_DATE
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Payment Method</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_METHOD
            }}</span>
          </template>
        </x-tooltip>
      </th>

      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Price(without VAT)</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_METHOD
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">VAT</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_METHOD
            }}</span>
          </template>
        </x-tooltip>
      </th>

      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Total Price</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_PRICE
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Discount Value</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_DISCOUNT_VALUE
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Total Amount</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_TOTAL_AMOUNT
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Collected Amount</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_COLLECTED_AMOUNT
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Payment Status</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_STATUS
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <x-tooltip>
          <span class="border-b border-dotted">Payment Allocation Status</span>
          <template #tooltip>
            <span class="custom-tooltip-content">{{
              paymentTooltipEnum.PAYMENT_MANAGEMENT_PAYMENT_ALLOCATION_STATUS
            }}</span>
          </template>
        </x-tooltip>
      </th>
      <th class="inner-th-class">
        <span>Actions</span>
      </th>
    </tr>
  </thead>
</template>
