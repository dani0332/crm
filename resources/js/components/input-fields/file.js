import React, {useState, useEffect} from 'react';
import {useDropzone} from 'react-dropzone';
import styled from "styled-components";
import  { ScaleLoader } from "react-spinners";
import Viewer from 'react-viewer';

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
    const [ visible, setVisible ] = React.useState(false);
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
          accept: 'image/*,application/pdf',
          onDrop: async acceptedFiles => {
            setFiles( { ...files, loader: true } )
            const file = acceptedFiles[0]
            const data = new FormData()
            data.append('file', file)
            const uploadFile = await fetch('/resource/store', {
                method: 'POST',
                body: data
              })

            const response = await uploadFile.json()
            const fileExt = file.name.split('.').pop();
            if(fileExt !== 'pdf') {
                const fileObj = Object.assign(file, {
                    preview: URL.createObjectURL(file),
                    response: response?.data
                })
                setFiles( { files: [fileObj], loader: false } )
            }else{
                setFiles( { files: [{ path: null, name: file.name,  response: response?.data }], loader: false } )
            }
          }
        });

  const thumbs = files.files.map(file => {

    if(!file.path){
        return <li>
            <a>
            <span className="image"><i className="fa fa-file-pdf-o" style={{fontSize: '3.5em'}}></i></span>
            <span> {file.name}</span>
            <span>
                <span className="time"><a className="close-link" onClick={()=> {
                    setFiles({ files:[],loader:false })
                    controller.onChange(null);
                }}><i className="fa fa-close"></i></a></span>
            </span>
            </a>
        </li>
    }
    return <li key={file.path}>
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
});

 const showDocumentPreview = () => {
    setVisible(true)
 }

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
    <Viewer
      visible={visible}
      onClose={() => { setVisible(false); } }
      images={[{src: `https://myalfreddev.blob.core.windows.net/myrewards/${field.value}`}]}
    />
      <ul className="list-unstyled msg_list">
        {thumbs}
        { shouldShowPreview === true && field.value && field.value.split('.').pop() !=='pdf' &&
            <li>
             <a style={{cursor:'pointer'}} onClick={showDocumentPreview} >
                <span className="image"><img src={`https://myalfreddev.blob.core.windows.net/myrewards/${field.value}`} style={{height:90,width:'auto'}}/></span>
                <span> {field.value}</span>
             </a>
            </li>
        }
        { shouldShowPreview === true && field.value && field.value.split('.').pop() ==='pdf' &&
            <li>
             <a style={{cursor:'pointer'}} onClick={()=>window.open(`https://myalfreddev.blob.core.windows.net/myrewards/${field.value}`, '_blank')}>
                <span className="image"><i className="fa fa-file-pdf-o" style={{fontSize: '3.5em'}}></i></span>
                <span> {field.value}</span>
             </a>
            </li>
        }
      </ul>
    </div>
  );
}


