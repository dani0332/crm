const advisorToOe = {
    getForm() {
      const form = {
        db_table: 'car_quote_assign_oe_to_advisor',
        title: 'Assign OE to Advisor',
        subtitle: '',
        access: {
          read: ['admin'],
          write: ['admin'],
          update: ['admin'],
          delete: ['admin'],
        },
        fields: {
          advisor_id: {
            type: 'dropdown',
            label: 'Advisor',
            source: 'users',
            filter: {name: 'advisor'},
            access: {
              read: ['admin'],
              write: ['admin'],
              update: ['admin'],
            },
            rules: { required: true },
            transform(item) {
              if (Array.isArray(item)) {
                const items = item.map(u => {
                  return { value: u?.id, label: u?.name };
                });
                return items;
              } else return { value: item?.id, label: item?.name };
            },
          },
          oe_id: {
            type: 'dropdown',
            label: 'OE',
            source: 'users',
            filter: {name: 'oe'},
            access: {
              read: ['admin'],
              write: ['admin'],
              update: ['admin'],
            },
            rules: { required: true },
            transform(item) {
              if (Array.isArray(item)) {
                const items = item.map(u => {
                  return { value: u?.id, label: u?.name };
                });
                return items;
              } else return { value: item?.id, label: item?.name };
            },
          },
        },
        sections: [
          {
            label: 'Assign OE to Advisor',
            fields: ['advisor_id','oe_id'],
          },
        ],
        view: {
          label: 'Assign OE to Advisor',
          find: {
            basic: [
              {
                type: 'dropdown',
                label: 'Advisor',
                source: 'users',
                field: 'advisor_id',
                filter: {name: 'advisor'},
                transform(item) {
                  if (Array.isArray(item)) {
                    const items = item.map(u => {
                      return { value: u?.id, label: u?.name };
                    });
                    return items;
                  } else return { value: item?.id, label: item?.name };
                },
              },
              {
                type: 'dropdown',
                label: 'OE',
                source: 'users',
                field: 'oe_id',
                filter: {name: 'oe'},
                transform(item) {
                  if (Array.isArray(item)) {
                    const items = item.map(u => {
                      return { value: u?.id, label: u?.name };
                    });
                    return items;
                  } else return { value: item?.id, label: item?.name };
                },
              },
            ],
            advanced: [],
          },
          columns: [
            {
                Header: 'ID',
                accessor: 'id',
            },
            {
                Header: 'Advisor',
                accessor:  d => `${d?.advisor_id?.name}`,
            },
            {
                Header: 'Advisor Email',
                accessor:  d => `${d?.advisor_id?.email}`,
            },
            {
                Header: 'OE',
                accessor:  d => `${d?.oe_id?.name}`,
            },
            {
                Header: 'OE Email',
                accessor:  d => `${d?.oe_id?.email}`,
            }
          ]
        },
      };
      return form;
    },
  };
  export default advisorToOe;