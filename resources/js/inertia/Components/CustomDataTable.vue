<script setup></script>

<template>
  <div class="vue3-easy-data-table custom-height">
    <div class="vue3-easy-data-table__main fixed-header hoverable border-cell">
      <table id="">
        <thead class="vue3-easy-data-table__header">
          <tr>
            <th class="">
              <span class="header direction-left"
                ><span class="header-text">Done</span
                ><!----><!----></span
              >
            </th>
            <th class="">
              <span class="header direction-left">
                <span class="header-text">Title</span>
                <!----><!---->
              </span>
            </th>
            <th class="">
              <span class="header direction-left"
                ><span class="header-text">Client Name</span
                ><!----><!----></span
              >
            </th>
            <th class="">
              <span class="header direction-left"
                ><span class="header-text">Followup Date</span
                ><!----><!----></span
              >
            </th>
            <th class="">
              <span class="header direction-left"
                ><span class="header-text">Assigned To</span
                ><!----><!----></span
              >
            </th>
            <th class="">
              <span class="header direction-left"
                ><span class="header-text">Action</span
                ><!----><!----></span
              >
            </th>
          </tr>
        </thead>
        <tbody class="vue3-easy-data-table__body">
          <slot name="body" />
        </tbody>
      </table>
      <!---->
      <div class="vue3-easy-data-table__message">No Available Data</div>
    </div>
  </div>
</template>
