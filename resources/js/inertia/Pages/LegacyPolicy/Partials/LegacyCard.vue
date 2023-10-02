<script setup>
import {formatDate} from '../../../Composables/utilities.js';
const props = defineProps({
  legacy: Object,
  type: String,
  title: String
});

// Function to format the label
const formatLabel = (inputString) => {  
  const stringWithSpaces = inputString.replace(/_/g, ' ');
  const words = stringWithSpaces.split(' ');
  for (let i = 0; i < words.length; i++) {
    words[i] = words[i][0].toUpperCase() + words[i].substring(1);
  }
  const camelCaseString = words.join(' ');
  return camelCaseString;
}
</script>

<template>
  <!-- Show lead history data -->
  <template v-if="type == 'single'">    
      <table>
        <tbody>
          <tr><td colspan="2"><strong>{{ title }}</strong></td></tr>
          <div v-for="(mainRecord, index) in legacy" :key="index">        
            <tr>
              <template v-if="index !== '_id'">
                <th>{{ formatLabel(index) }}</th>
                <td>
                  {{ index.toLowerCase().includes('date') ? formatDate(mainRecord) : mainRecord }}
                </td>
              </template>
            </tr>
          </div>        
        </tbody>
      </table>
  </template>
  <template v-if="type == 'multiple'">  
    <div v-for="(mainRecord, index) in legacy" :key="index">
      <table>
        <tbody>
            <tr v-if="title!=''"><td colspan="2"><strong>{{ title }}</strong></td></tr>          
            <tr v-for="(subRecord, subIndex) in mainRecord" :key="subIndex">
              <template v-if="subIndex !== '_id'">
                <th>{{ formatLabel(subIndex) }}</th>
                <td>                  
                  {{ subIndex.toLowerCase().includes('date') ? formatDate(subRecord) : subRecord }}
                </td>
              </template>
            </tr>        
        </tbody>
      </table>
    </div> 
  </template>  
</template>

<style scoped>
/* Apply CSS styling to table headers */
table {
  font-size: 12px;
  margin-right: 20px;
}
table td {
  padding: 5px;
  vertical-align: top;
}
table th {
  background-color: rgb(25, 113, 163);
  color: white;
  padding: 5px;
  min-width:150px;
  text-align: left;  
}
</style>
