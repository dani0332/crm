import React from 'react';

export default function CarQuoteAddon(props) {

  if(!props.field?.value || props.field.value.length === 0)
      return (<div className='row'><div className='col-md-12'></div></div>)

    return (
        <div className='row'>
          <div className='col-md-12'>
            <div className=''>
              <div className='x_content'></div>
              <table style={{width: '100%'}}> 
                { props?.field && props.field?.value.map((addOnObj, i) => {
                    const name = addOnObj?.addon_option_id?.addon_id?.text
                    const value = addOnObj?.addon_option_id?.value
                   return (<tr className='border-top' style={{fontSize:13, lineHeight: "30px"}}><td>{name}:</td><td className="fs15 fw700 text-right">{value}</td></tr>)
                })}
              </table>
            </div>
          </div>
        </div>
    )
}