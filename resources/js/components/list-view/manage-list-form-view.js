import React, {  useRef, useState, useEffect, useReducer } from "react";
import styled from "styled-components";
import List from "../../components/list-view/list"
import DrawForm from "../form/draw-form";
const Styles = styled.div``;
import { useSelector, useDispatch } from 'react-redux';
import { calculatePermissionAccess } from "../../utils";
import { FormContextProvider } from "../form/FormContext";
import  { ScaleLoader } from "react-spinners";
import { confirmAlert } from 'react-confirm-alert';

function ManageListFormView(props) {

    const initialState = props.form
    function reducer(state, action) {
        switch (action.type) {
            case 'read':
            case 'edit':
            case 'list':
            case 'new':
                return  { ...action.obj};
            case 'showLoader':
                return { ...action.obj};
            case 'reset':
                return { }
            default:
                return state;
        }
    }

    const dispatch = useDispatch()
    const [form, manageDispatch] = useReducer(reducer, {});
    let permission = calculatePermissionAccess(form?.access)

    console.log('*************ManageListFormView---manage-list-form-view.js*************')
    console.log(form)
    console.log(permission)
    console.log(initialState)

    useEffect(() => {
        console.log('**************ManageListFormView--useEffect--ReducerContainerState-manage-list-form-view.js************')
        dispatch({ type: 'VISIBLE_FORM',selectedRecord: null, initialForm: initialState, form: form, formState: 'list', manageListDispatch: manageDispatch})
    }, [initialState])

 if(form?.loader === true)
    return <div className="sweet-loading"><ScaleLoader color={'#000000'} loading={true}   size={150} /></div>


const { selectedRecord } = form

function deleteConfirm() {

    confirmAlert({
        title: 'Delete',
        message: 'Are you sure to delete?',
        buttons: [
          {
            label: 'Yes',
            onClick: async () => {
                dispatch({ type: 'VISIBLE_FORM',selectedRecord: selectedRecord, initialForm: initialState, form: form, formState: 'delete', manageListDispatch: manageDispatch })
            }
          },
          {
            label: 'No',
            onClick: () => {}
          }
        ]
    });
}

return (
    <Styles>
      <div className="row x_panel">
            <div className="col-md-12 col-sm-12 ">
                <div className="">
                { form.action_type && form.action_type === "list"  &&
                    <div className="x_title">
                        <h2>{form.title}</h2>
                        <ul className="nav navbar-right panel_toolbox">
                            { permission.write === true &&
                                <li><i className="fa fa-plus" style={{ paddingTop: 3 }}></i>
                                    <a
                                    style={{display: 'inline'}}
                                    onClick={()=> { dispatch({ type: 'VISIBLE_FORM',selectedRecord: selectedRecord, initialForm: initialState, form: form, formState: 'new', manageListDispatch: manageDispatch }) }}
                                    >Add new</a>
                                </li>
                            }
                        </ul>
                        <div className="clearfix"></div>
                    </div>
                }
                { form.action_type && form.action_type === "read"  &&
                    <div className="">
                        <h2></h2>
                        <ul className="nav navbar-right panel_toolbox">
                            <li><i className="fa fa-times" style={{ paddingTop: 3 }}></i>
                                <a style={{display: 'inline'}}
                                    onClick={()=> {
                                        dispatch({ type: 'VISIBLE_FORM',selectedRecord: selectedRecord, initialForm: initialState, form: form, formState: 'list', manageListDispatch: manageDispatch })
                                    }}
                                >Cancel</a>
                            </li>
                            { permission.delete === true &&
                                <li><i className="fa fa-trash" style={{ paddingTop: 3 }}></i>
                                    <a style={{display: 'inline'}}
                                    onClick={()=> {
                                        deleteConfirm()
                                    }} >Delete</a>
                                </li>
                            }
                            { permission.write === true &&
                                <li><i className="fa fa-pencil" style={{ paddingTop: 3 }}></i>
                                    <a style={{display: 'inline'}}
                                    onClick={()=> {
                                        dispatch({ type: 'VISIBLE_FORM',selectedRecord: selectedRecord, initialForm: initialState, form: form, formState: 'edit', manageListDispatch: manageDispatch })
                                    }} >Edit</a>
                                </li>
                            }
                        </ul>
                        <div className="clearfix"></div>
                    </div>
                }
                <div className="x_content">
                    { form.view_mode && form.view_mode === "form"  && <FormContextProvider value={{ initialForm: initialState, form: form, manageListDispatch:manageDispatch  }}><DrawForm  manageListDispatch={manageDispatch} listDispatch={dispatch} options={form} /> </FormContextProvider>}
                    { permission.read === true && form.view_mode && form.view_mode === "list"  && <FormContextProvider value={{ initialForm: initialState, form: form, manageListDispatch:manageDispatch }}><List manageListDispatch={manageDispatch}  dispatch={dispatch} form={form} /> </FormContextProvider>}
                    { permission.read === false &&
                        <h1>Access Denied</h1>
                    }
                </div>
                </div>
            </div>
      </div>

    </Styles>
)
}
export default ManageListFormView;
