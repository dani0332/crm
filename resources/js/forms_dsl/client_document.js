const json = {
    columns: [
        {
          Header: "CDB ID",
          accessor: "code",
          canSort:true
        },

        {
          Header: "Client Name",
          accessor: d => `${d.first_name} ${d.last_name}`
        },
        {
          Header: "Email",
          accessor: "email"
        },
        {
          Header: "Contact Number",
          accessor: "mobile_no"
        },
        {
          Header: "Created on",
          accessor: "created_at"
        },
      ],
    filter: {
        url:'',
        filter: [
            {
                type:'text',
                label:'CDB ID',
                field:'code',
                validation:{}
            },
            {
                type:'text',
                label:'Email',
                field:'email',
                validation:{}
            }
        ]
    }
}
