import React, { useState } from "react";
import DatePicker from "react-datepicker";

import "react-datepicker/dist/react-datepicker.css";


export default function DatePickerField({field, controller}) {
  const [startDate, setStartDate] = useState();
  return (
    <DatePicker selected={startDate} onChange={(date) => setStartDate(date)} />
  );
}
