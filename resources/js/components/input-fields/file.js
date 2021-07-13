import React, {useState} from 'react';
import {useDropzone} from 'react-dropzone';
import styled from "styled-components";
import useFetch from 'use-http'


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

export default function File({field , register, controller}) {
    const [files, setFiles] = useState([]);
    const { post } = useFetch('resource/store')

    const {
        getRootProps,
        getInputProps,
        isDragActive,
        isDragAccept,
        isDragReject
      } = useDropzone({
          accept: 'image/*',
          onDrop: async acceptedFiles => {
            const file = acceptedFiles[0]
            const data = new FormData()
            data.append('file', file)
            const response = await post(data)
            const fileObj = Object.assign(file, {
                preview: URL.createObjectURL(file)
            })
            // const getFiles = acceptedFiles.map(file => Object.assign(file, {
            //     preview: URL.createObjectURL(file)
            //   }));
            controller.onChange(response?.data);
            setFiles([fileObj]);
          }
        });

  const thumbs = files.map(file => (
    <li key={file.path}>
        <a>
        <span className="image"><img src={file.preview} style={{height:90,width:'auto'}}/></span>
        <span>{file.name}</span>
        <span>
            <span className="time"><a className="close-link" onClick={()=>setFiles([])}><i className="fa fa-close"></i></a></span>
        </span>
        </a>
    </li>
  ));

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


