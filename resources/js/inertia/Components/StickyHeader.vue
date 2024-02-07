<template>
  <x-card class="sticky shadow z-20 top-[60px] py-4 rounded-lg px-2">
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-5">
        <slot name="header"></slot>
      </div>
      <div class="flex items-center space-x-2">
        <slot></slot>
      </div>
    </div>
  </x-card>
</template>