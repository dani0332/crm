import React from 'react';
import { useState } from 'react';
import Step1Wizard from './step_1';
import Step2Wizard from './step_2';
import Step3Wizard from './step_3';

function DashboardFtcWizard() {
  const [steps, setSteps] = useState({
    steps: [
      { index: 1, title: ' Client Documentation', desc: '' },
      { index: 2, title: 'FTC Details', desc: '' },
      { index: 3, title: 'KYC & AML Details', desc: '' },
    ],
    currentIndex: 1,
  });

  const moveNext = () => {
    setSteps({ ...steps, currentIndex: steps.currentIndex + 1 });
  };
  const movePrevious = () => {
    setSteps({ ...steps, currentIndex: steps.currentIndex - 1 });
  };

  return (
    <div className='row'>
      <div className='col-md-12 col-sm-12 '>
        <div className='x_panel'>
          <div className='x_title'>
            <h2>
              FTC Form <small>Wizard</small>
            </h2>
            <div className='clearfix'></div>
          </div>
          <div className='x_content'>
            <div className='form_wizard wizard_horizontal' id='wizard'>
              <ul className='wizard_steps anchor'>
                {steps.steps &&
                  steps.steps.map((u, i) => {
                    return (
                      <li key={i}>
                        <a
                          href='javascript:void(0)'
                          className={`${
                            steps.currentIndex == u.index
                              ? 'selected'
                              : 'disabled'
                          }`}
                          rel='1'
                        >
                          <span className='step_no'>{u.index}</span>
                          <span className='step_descr'>{u.title}</span>
                        </a>
                      </li>
                    );
                  })}
              </ul>
            </div>
            <div className='stepContainer'>
              {steps.currentIndex && steps.currentIndex == 1 && <Step1Wizard />}
              {steps.currentIndex && steps.currentIndex == 2 && <Step2Wizard />}
              {steps.currentIndex && steps.currentIndex == 3 && <Step3Wizard />}
            </div>
            <div className='actionBar'>
              <a
                href='#'
                className='buttonFinish buttonDisabled btn btn-default'
              >
                Finish
              </a>
              <a
                href='javascript:void(0)'
                className={`buttonNext btn btn-primary ${
                  steps.currentIndex >= steps.steps.length
                    ? 'buttonDisabled'
                    : ''
                }`}
                onClick={moveNext}
              >
                Next
              </a>
              <a
                href='javascript:void(0)'
                className={`buttonPrevious btn btn-primary ${
                  steps.currentIndex > 1 ? '' : 'buttonDisabled'
                }`}
                onClick={movePrevious}
              >
                Previous
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
export default DashboardFtcWizard;
