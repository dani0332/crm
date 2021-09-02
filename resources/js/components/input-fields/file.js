import React, {useState, useEffect} from 'react';
import {useDropzone} from 'react-dropzone';
import styled from "styled-components";
import useFetch from 'use-http'
import  { ScaleLoader } from "react-spinners";

const getColor = (props) => {
    if (props.isDragAccept) {
        return '#00e676';
    }
    if (props.isDragReject) {
        return '#ff1744';
    }
    if (props.isDragActive) {
        return '#2196f3';
    }
    return '#eeeeee';
  }

const Container = styled.div`
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  border-width: 2px;
  border-radius: 2px;
  border-color: ${props => getColor(props)};
  border-style: dashed;
  background-color: #fafafa;
  color: #bdbdbd;
  outline: none;
  transition: border .24s ease-in-out;
`;

export default function File({field, controller}) {

    console.log("********* -> File-Render********->")
    console.log(field)

    const [files, setFiles] = useState({files: [], loader: false});
    const { post } = useFetch('resource/store')
    useEffect(() => {
        controller.onChange(null);
    },[])


    const {
        getRootProps,
        getInputProps,
        isDragActive,
        isDragAccept,
        isDragReject
      } = useDropzone({
          accept: 'image/*',
          onDrop: async acceptedFiles => {

            setFiles( { ...files, loader: true } )
            const file = acceptedFiles[0]
            const data = new FormData()
            data.append('file', file)
            const response = await post(data)
            const fileObj = Object.assign(file, {
                preview: URL.createObjectURL(file),
                response: response?.data
            })
            setFiles( { files: [fileObj], loader: false } )
          }
        });

  const thumbs = files.files.map(file => (
    <li key={file.path}>
        <a>
        <span className="image"><img src={file.preview} style={{ height:90, width:'auto' }}/></span>
        <span>{file.name}</span>
        <span>
            <span className="time"><a className="close-link" onClick={()=> {
                setFiles({ files:[],loader:false })
                controller.onChange(null);
            }}><i className="fa fa-close"></i></a></span>
        </span>
        </a>
    </li>
  ));

  const shouldShow = (field?.formState && field.formState === 'read' ) ? false : true
  let shouldShowPreview = false
  if(field?.formState && ( field.formState === 'read' || field.formState === 'edit' ) ){
     shouldShowPreview = true
     controller.onChange(field?.value);
  }

  if(files.files.length > 0){
    const fileObj = files.files[0]
    controller.onChange(fileObj?.response);
    shouldShowPreview = false
  }

  return (
    <div className="container">
       <div className="sweet-loading"><ScaleLoader color={'#000000'} loading={files.loader}   size={150} /></div>
     { shouldShow === true && files.loader === false &&
      <Container {...getRootProps({isDragActive, isDragAccept, isDragReject})}>
        <input {...getInputProps()} />
        <p>Drag 'n' drop some files here, or click to select files</p>
      </Container>
    }
      <ul className="list-unstyled msg_list">
        {thumbs}
        { shouldShowPreview === true && field.value &&
            <li>
             <a>
                <span className="image"><img src={`https://myalfreddev.blob.core.windows.net/myrewards/${field.value}`} style={{height:90,width:'auto'}}/></span>
                <span>{field.value}</span>
             </a>
            </li>
        }
      </ul>
    </div>
  );
}


