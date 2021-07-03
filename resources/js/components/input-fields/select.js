import React, {  useEffect, useState } from "react";

export default function SelectField({field,register}) {
  return (
    <select {...register(field.field)} className="form-control">
        <option value="">-Select-</option>
    { field.data && field.data.map((u,i) => {
        return (
            <option key={i} value={u.id}>{u.title}</option>
        )
    })
    }
    </select>
  );
}
