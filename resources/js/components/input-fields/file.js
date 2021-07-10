import React, {useState} from 'react';
import {useDropzone} from 'react-dropzone';
import styled from "styled-components";



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

export default function File({field,register}) {
    const [files, setFiles] = useState([]);
    const {
        getRootProps,
        getInputProps,
        isDragActive,
        isDragAccept,
        isDragReject
      } = useDropzone({
          accept: 'image/*',
          onDrop: acceptedFiles => {

            console.log(acceptedFiles)
            setFiles(acceptedFiles.map(file => Object.assign(file, {
              preview: URL.createObjectURL(file)
            })));
          }
        });

  const thumbs = files.map(file => (
    <li key={file.path}>
        <a>
        <span className="image"><img src={file.preview} style={{height:90,width:'auto'}}/></span>
        <span>{file.name}</span>
        <span>
            <span className="time"><a className="close-link" onClick={()=>console.log('close')}><i className="fa fa-close"></i></a></span>
        </span>
        </a>
    </li>

  ));

  console.log(thumbs)

  return (
    <div className="container">
      <Container {...getRootProps({isDragActive, isDragAccept, isDragReject})}>
        <input {...getInputProps()} />
        <p>Drag 'n' drop some files here, or click to select files</p>
      </Container>
      <ul className="list-unstyled msg_list">
        {thumbs}
      </ul>
    </div>
  );
}


