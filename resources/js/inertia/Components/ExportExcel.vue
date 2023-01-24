<template>
  <button @click="exportExcel">
    <slot></slot>
  </button>
</template>

<script>
import XLSX from 'xlsx';
export default {
  name: 'ExportExcel',
  props: {
    columns: {
      type: Array,
      default: [],
    },
    data: {
      type: Array,
      default: [],
    },
    filename: {
      type: String,
      default: 'excel',
    },
    sheetname: {
      type: String,
      default: 'SheetName',
    },
  },

  data() {
    return {
      dadosAux: [],
    };
  },
  methods: {
    exportExcel() {
      this.$emit('loading', true);
      let createXLSLFormatObj = [];
      let newXlsHeader = [];
      let newXlsData = [];
      let filename = this.filename + '.xlsx';
      let ws_name = this.sheetname;

      if (this.columns.length === 0) {
        this.$emit('error', { message: 'Without columns!', error: 'column' });
        return;
      }
      if (this.data.length === 0) {
        this.$emit('error', { message: 'Without data!', error: 'data' });
        return;
      }

      newXlsHeader = this.columns.map(e => e.text);
      setTimeout(() => {
        newXlsData = this.data.map(value => {
          let innerRowData = [];
          this.columns.forEach(val => {
            if (val.dataFormat && typeof val.dataFormat === 'function') {
              innerRowData.push(val.dataFormat(value[val.value]));
            } else {
              innerRowData.push(value[val.value]);
            }
          });
          return innerRowData;
        });

        createXLSLFormatObj = [newXlsHeader].concat(newXlsData);

        let wb = XLSX.utils.book_new(),
          ws = XLSX.utils.aoa_to_sheet(createXLSLFormatObj);

        XLSX.utils.book_append_sheet(wb, ws, ws_name);
        XLSX.writeFile(wb, filename);
        this.$emit('loading', false);
      }, 100);
    },
  },
};
</script>
