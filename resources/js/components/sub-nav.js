import React, { useDebugValue, useState} from "react";
import styled from "styled-components";
import _ from "lodash";
const Nav = styled.div`
    .quick-list {
        border-right: 1px #cccc solid;
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

export default function SubNaV({ listNav, onSelect}) {
const [data, setData] = useState(listNav)

const selectList = (u) => {

    onSelect(u)
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
                        <li  className={className} key={i} onClick={()=>selectList(u)}><i className={u.icon}></i><a href="javascript:void(0)">{u.label}</a></li>
                    )
                })
            }
        </ul>
    </Nav>
  );
}
