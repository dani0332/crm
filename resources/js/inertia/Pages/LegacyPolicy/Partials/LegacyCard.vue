<script setup>
const props = defineProps({
  legacy: Object,
  type: String,
  title: String,
  policy: Object,
});

const skipFields = [
  '_id',
  'email',
  'phone',
  'idcode',
  'mobile_phone',
  'profile_data',
  'name',
  'policy_no',
  'policy_oid',
];

if (props.title == 'CUSTOMER') {
  skipFields.push('customer');
}

// Function to format the label
const formatLabel = inputString => {
  const stringWithSpaces = inputString.replace(/_/g, ' ');
  const words = stringWithSpaces.split(' ');
  for (let i = 0; i < words.length; i++) {
    words[i] = words[i][0].toUpperCase() + words[i].substring(1);
  }
  const camelCaseString = words.join(' ');
  return camelCaseString;
};
const dateFormat = date => {
  if (date) {
    if (date.$date && date.$date.$numberLong) {
      date = formatDate(date);
    }
    return useDateFormat(date, 'DD-MM-YYYY').value;
  }
  return null;
};
</script>

<template>
  <!-- Show lead history data -->
  <template v-if="type == 'single'">
    <table>
      <tbody>
        <tr>
          <td class="fixheight" colspan="2">
            <strong>{{ title }}</strong>
          </td>
        </tr>

        <tr v-for="(mainRecord, index) in legacy" :key="index">
          <template v-if="!skipFields.includes(index)">
            <th>{{ formatLabel(index) }}</th>
            <td>
              {{
                index.toLowerCase().includes('date')
                  ? dateFormat(mainRecord)
                  : mainRecord
                    ? mainRecord
                    : ''
              }}
            </td>
          </template>
        </tr>

        <template v-if="title == 'CUSTOMER'">
          <tr>
            <td colspan="2"></td>
          </tr>
          <tr v-for="(mainRecord, index) in policy.policy" :key="index">
            <template v-if="!skipFields.includes(index)">
              <th>{{ formatLabel(index) }}</th>
              <td>
                {{
                  index.toLowerCase().includes('date')
                    ? dateFormat(mainRecord)
                    : mainRecord
                      ? mainRecord
                      : ''
                }}
              </td>
            </template>
          </tr>
        </template>

        <template v-if="title == 'QUOTE'">
          <tr>
            <td colspan="2"></td>
          </tr>
          <tr v-for="(mainRecord, index) in policy.vehicle" :key="index">
            <template v-if="!skipFields.includes(index)">
              <th>{{ formatLabel(index) }}</th>
              <td>
                {{
                  index.toLowerCase().includes('date')
                    ? dateFormat(mainRecord)
                    : mainRecord
                      ? mainRecord
                      : ''
                }}
              </td>
            </template>
          </tr>
        </template>

        <template v-if="title == 'QUOTE'">
          <tr>
            <td colspan="2"></td>
          </tr>
          <tr v-for="(mainRecord, index) in policy.casco" :key="index">
            <template v-if="!skipFields.includes(index)">
              <th>{{ formatLabel(index) }}</th>
              <td>
                {{
                  index.toLowerCase().includes('date')
                    ? dateFormat(mainRecord)
                    : mainRecord
                      ? mainRecord
                      : ''
                }}
              </td>
            </template>
          </tr>
        </template>

        <template v-if="title == 'QUOTE'">
          <tr>
            <td colspan="2"></td>
          </tr>
          <tr
            v-for="(mainRecord, index) in policy.additional_information"
            :key="index"
          >
            <template v-if="!skipFields.includes(index)">
              <th>{{ formatLabel(index) }}</th>
              <td>
                {{
                  index.toLowerCase().includes('date')
                    ? dateFormat(mainRecord)
                    : mainRecord
                      ? mainRecord
                      : ''
                }}
              </td>
            </template>
          </tr>
        </template>
      </tbody>
    </table>
  </template>
  <template v-if="type == 'multiple'">
    <template v-if="Object.keys(legacy).length > 0">
      <div v-for="(mainRecord, index) in legacy" :key="index">
        <table>
          <tbody>
            <tr v-if="title !== ''">
              <td class="fixheight" colspan="2">
                <strong>{{ title }}</strong>
              </td>
            </tr>
            <tr v-for="(subRecord, subIndex) in mainRecord" :key="subIndex">
              <template v-if="!skipFields.includes(subIndex)">
                <th>{{ formatLabel(subIndex) }}</th>
                <td>
                  {{
                    subIndex.toLowerCase().includes('date')
                      ? dateFormat(subRecord)
                      : subRecord
                        ? subRecord
                        : ''
                  }}
                </td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
    <template v-else-if="title == ''">
      <p>No record found</p>
    </template>
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
  min-width: 150px;
  text-align: left;
}

.fixheight {
  height: 20px;
}
</style>
