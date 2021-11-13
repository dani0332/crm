export function* fillConditionalFields(obj) {
  const { fields, selectedRecord } = obj;
  Object.entries(fields).forEach(entry => {
    const [key, value] = entry;
    const getField = value;
    const recVal = selectedRecord?.[key];
    if (recVal && getField?.if) {
      let selectedVal = {};
      if (typeof getField?.transform === 'function') {
        selectedVal = getField?.transform(recVal);
      } else if (typeof recVal === 'object') {
        selectedVal = { ...recVal, selected: recVal?.text };
      } else {
        selectedVal = { selected: recVal };
      }

      const val = getField.if[selectedVal?.selected] ? getField.if[selectedVal.selected]: getField.if[selectedVal.id];
      if (val?.fields) {
        const getCondFields = val.fields;
        if (getCondFields.length > 0) {
          getCondFields.forEach(element => {
            obj.fields[element].offscreen = false;
          });
        }
        console.log(getCondFields);
      } else {
        const getCondFields = getField.else;
        getCondFields.forEach(element => {
          obj.fields[element].offscreen = true;
          obj.fields[element].value = null;
        });
      }
    }
  });
}
