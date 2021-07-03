import React, {  useEffect, useState } from "react";

export default function InputField({field,register}) {
  return (
    <input {...register(field.field)} className="form-control"/>
  );
}
