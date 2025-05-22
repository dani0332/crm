<template>
  <section class="fixed right-2 top-2 z-50 w-full sm:w-auto">
    <div
      class="flex w-full items-center rounded-md border border-secondary-700 bg-secondary-800 px-4 py-3 text-white dark:border-secondary-100 dark:bg-secondary-50 dark:text-secondary-900"
      style="
        --x-notification-icon: #f87171;
        --x-notification-action: rgb(85, 148, 196);
        --x-notification-action-hover: #1c83bc;
        --x-notification-dark-icon: #ef4444;
        --x-notification-dark-action: #1c83bc;
        --x-notification-dark-action-hover: rgb(27, 124, 178);
      "
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="x-icon mr-4 inline h-4 w-4 shrink-0 text-[color:var(--x-notification-icon)] dark:text-[color:var(--x-notification-dark-icon)]"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path
          fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd"
        ></path>
      </svg>
      <div class="flex flex-wrap items-center">
        <span class="mr-2 font-medium"> <slot /> </span><span> </span>
      </div>
    </div>
  </section>
</template>
