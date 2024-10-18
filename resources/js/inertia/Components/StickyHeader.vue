<template>
  <x-card class="sticky shadow z-20 top-[60px] rounded-lg p-3">
    <div
      class="flex justify-between items-center flex-wrap max-[786px]:flex-col"
    >
      <div class="flex items-center gap-5">
        <slot name="header"></slot>
      </div>
      <div
        class="flex items-center space-x-2 max-[600px]:flex-col max-[786px]:gap-3"
      >
        <slot></slot>
      </div>
    </div>
  </x-card>
</template>
