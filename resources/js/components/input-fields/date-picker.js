import React, { useState, useEffect } from "react";
import DatePicker from "react-datepicker";
import moment from "moment";
import "react-datepicker/dist/react-datepicker.css";


export default function DatePickerField({field, controller}) {
  const [startDate, setStartDate] = useState(null);

  useEffect(() => {
    controller.onChange(null);
 },[])


  const onChange = date => {

    const formatDate = moment(date).format('YYYY/MM/DD').toString();
    controller.onChange(formatDate)
    if (typeof field?.dispatch === 'function') {
        field.dispatch({type : 'setValue', field: { name: field.field, value :  formatDate }});
    }
}

let shouldDisable = false
if(field?.formState && ( field.formState === 'read' ) ){
    shouldDisable = true
}

return (
    <DatePicker
        disabled={shouldDisable}
        className="form-control"
        dateFormat="yyyy/MM/dd"
        selected={ startDate ? startDate : (field?.value ? new Date(field.value) : null) }
        onChange={onChange}
    />
  );
}
