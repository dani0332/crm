

import React, { useEffect, useState  } from "react";
import { useParams } from "react-router-dom";
import { confirmAlert } from 'react-confirm-alert'; // Import
import 'react-confirm-alert/src/react-confirm-alert.css'; // Import css
import { dispatchPromise } from "../../sagas";
import {  useDispatch } from 'react-redux';
import  { ScaleLoader } from "react-spinners";
import { session } from "../../utils";

export default function AssignUser(props) {

    const { role , id } = session();
    if(role === 'advisor')
        return (<h1>Access Denied</h1>)

    const dispatch = useDispatch()
    const [state, setState ] = useState({ loader : true , user: null});

    useEffect(() => {
        showAssignUser()
    }, [props]);

    const showAssignUser = () =>{
        dispatchPromise({
            dispatch: dispatch,
            options: {
                type: 'SEND_REQUEST',
                request: { url: `/form/car_quote_request/${props?.filter?.car_quote_id}`}
            }
        }).then((response) => {
            setState({loader: false, user: response?.data?.pa_id})
        }).catch(error => {});
    }

    const showMsgDialog =  async (obj) => {
        confirmAlert({
            title: 'Assignment',
            message: 'Are you sure to assign?',
            buttons: [
              {
                label: 'Yes',
                onClick: async () => {
                    dispatchPromise({
                        dispatch: dispatch,
                        options: {
                            type: 'SEND_REQUEST',
                            request: { url: `/form/car_quote_request/${props?.filter?.car_quote_id}`, method: 'PUT', body: JSON.stringify( { action: 'assign' } ) }
                        }
                    }).then((response) => {
                        showAssignUser()
                    }).catch(error => {});
                }
              },
              {
                label: 'No',
                onClick: () => {}
              }
            ]
        });
    }

    if(state?.loader === true)
        return  (<div className="sweet-loading"><ScaleLoader color={'#000000'} loading={true} size={150} /></div>)
    return(
        <div className="col-lg-12">
            <div className="x_title"><h2>Assign To:</h2><div className="clearfix"></div></div>
                <div className="x_content">
            { state?.user === null &&
                <div className="col-md-12 col-sm-12 ">
                        <div className="form-group row">
                                <label className="control-label col-md-3 col-sm-3 ">User </label>
                                <div className="col-md-6 col-sm-6">
                                    <input type="text" className="form-control" disabled="disabled" placeholder={props?.email} />
                                </div>
                        </div>
                        <div className="col-md-9">
                            <div className="pull-right">
                                <button type="submit" className="btn btn-success" onClick={showMsgDialog} >Assign</button>
                            </div>
                         </div>
                </div>
            }

            { state?.user && state.user !== null &&
                <h2>Already assigned you.</h2>
            }
            </div>
         </div>
    )
}
