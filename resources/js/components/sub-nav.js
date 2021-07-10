import React, { useDebugValue, useState} from "react";
import styled from "styled-components";
import _ from "lodash";
const Nav = styled.div`
    .quick-list {
        border-right: 1px #cccc solid;
        min-height: 500px;
        width:100%;
    }
    li {
        padding-left:1px !important;
    }
    .active{
            background-color:#ddd;
    }
}
`

export default function SubNaV() {

 const list = [
     {
         icon:'fa fa-calendar',
         label:'Setting',
         active: 0,
         id:1,
         data:{}
     },
     {
        icon:'fa fa-bar-chart',
        label:'Auto Renewal',
        active: 0,
        id:2,
        data:{}
    },
    {
        icon:'fa fa-line-chart',
        label:'Achievements',
        active: 0,
        id:3,
        data:{}
    },
    {
        icon:'fa fa-calendar',
        label:'Achievements',
        active: 0,
        id:4,
        data:{}
    },
    {
        icon:'fa fa-calendar',
        label:'Setting',
        active: 0,
        id:5,
        data:{}
    },
    {
        icon:'fa fa-line-chart',
        label:'Setting',
        active: 0,
        id:6,
        data:{}
    }
 ]

const [data, setData] = useState(list)
const selectList = (u) => {
    let newList = []
    data.forEach(element => {
        if(element.id === u.id){
            newList.push({...element,active:1})
        }else{
            newList.push({...element,active:0})
        }
    });
    setData(newList)
}

return (
      <Nav>
        <ul className="quick-list ">
            {
                data && data.map((u,i) =>{
                    let className = ''
                    if(u.active === 1)
                        className = 'active'
                    return (
                        <li className={className} key={i} onClick={()=>selectList(u)}><i className={u.icon}></i><a href="javascript:void(0)">{u.label}</a></li>
                    )
                })
            }
        </ul>
    </Nav>
  );
}
