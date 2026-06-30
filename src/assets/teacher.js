// teacher.js
// Multi-step form, validation, progress bar, etc.
window.initTeacherMultiStep = function() {
    const steps = document.querySelectorAll('.form-step');
    const progressSteps = document.querySelectorAll('.step');
    const progressBar = document.getElementById('progress-bar');
    let currentStep = 0;
    function updateProgressBar() {
        const progress = ((currentStep + 1) / steps.length) * 100;
        progressBar.style.width = `${progress}%`;
        progressSteps.forEach((step, index) => {
            if (index === currentStep) {
                step.classList.add('active', 'text-blue-600', 'font-semibold');
                step.classList.remove('text-gray-500');
                step.setAttribute('aria-current', 'step');
            } else {
                step.classList.remove('active', 'text-blue-600', 'font-semibold');
                step.classList.add('text-gray-500');
                step.removeAttribute('aria-current');
            }
        });
    }
    function showErrorMessage(elementId, message) {
        const errorElement = document.getElementById(`${elementId}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
    }
    function hideErrorMessage(elementId) {
        const errorElement = document.getElementById(`${elementId}-error`);
        if (errorElement) {
            errorElement.classList.add('hidden');
            errorElement.textContent = '';
        }
    }
    function validateStep(stepIndex) {
        const currentFormStep = steps[stepIndex];
        let isValid = true;
        let firstInvalidElement = null;
        let errorMessages = [];
        currentFormStep.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
        });
        currentFormStep.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
        });
        const requiredInputs = currentFormStep.querySelectorAll('input[required]:not([type="radio"]):not([type="checkbox"]), textarea[required]');
        requiredInputs.forEach(input => {
            if (input.type === 'file') {
                if (!input.files || input.files.length === 0) {
                    input.classList.add('border-red-500');
                    showErrorMessage(input.id, `กรุณาแนบไฟล์`);
                    if (!firstInvalidElement) {
                        firstInvalidElement = input;
                    }
                    isValid = false;
                } else if (input.accept === '.pdf' && input.files[0].type !== 'application/pdf') {
                    input.classList.add('border-red-500');
                    showErrorMessage(input.id, `กรุณาแนบเฉพาะไฟล์ PDF`);
                    if (!firstInvalidElement) {
                        firstInvalidElement = input;
                    }
                    isValid = false;
                }
            } else if (input.value.trim() === '') {
                input.classList.add('border-red-500');
                showErrorMessage(input.id, `กรุณากรอกข้อมูลในช่องนี้`);
                if (!firstInvalidElement) {
                    firstInvalidElement = input;
                }
                isValid = false;
            } else if (input.type === 'email' && !input.validity.valid) {
                input.classList.add('border-red-500');
                showErrorMessage(input.id, `กรุณากรอกอีเมลที่ถูกต้อง`);
                if (!firstInvalidElement) {
                    firstInvalidElement = input;
                }
                isValid = false;
            } else if (input.type === 'tel' && input.pattern && !input.value.match(input.pattern)) {
                input.classList.add('border-red-500');
                showErrorMessage(input.id, `กรุณากรอกเบอร์โทรศัพท์ 10 หลัก`);
                if (!firstInvalidElement) {
                    firstInvalidElement = input;
                }
                isValid = false;
            }
        });
        const optionalFileInputs = currentFormStep.querySelectorAll('input[type="file"]:not([required])');
        optionalFileInputs.forEach(input => {
            if (input.files && input.files.length > 0 && input.accept === '.pdf' && input.files[0].type !== 'application/pdf') {
                input.classList.add('border-red-500');
                showErrorMessage(input.id, `กรุณาแนบเฉพาะไฟล์ PDF`);
                if (!firstInvalidElement) {
                    firstInvalidElement = input;
                }
                isValid = false;
            }
        });
        currentFormStep.querySelectorAll('.checkbox-group-container').forEach(container => {
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            const isChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
            const isGroupRequired = container.querySelector('span.text-red-500') !== null;
            const errorMessageId = checkboxes.length > 0 ? checkboxes[0].name.replace('[]', '') + '-error' : null;
            if (isGroupRequired && !isChecked) {
                container.classList.add('border-red-500');
                if (errorMessageId) showErrorMessage(errorMessageId, `กรุณาเลือกอย่างน้อยหนึ่งข้อ`);
                if (!firstInvalidElement) {
                    firstInvalidElement = container;
                }
                isValid = false;
            } else {
                container.classList.remove('border-red-500');
                if (errorMessageId) hideErrorMessage(errorMessageId);
            }
        });
        currentFormStep.querySelectorAll('.radio-group-container').forEach(container => {
            const radios = container.querySelectorAll('input[type="radio"]');
            const isGroupRequired = Array.from(radios).some(radio => radio.hasAttribute('required'));
            const errorMessageId = radios.length > 0 ? radios[0].name + '-error' : null;
            if (isGroupRequired) {
                const isSelected = Array.from(radios).some(radio => radio.checked);
                if (!isSelected) {
                    container.classList.add('border-red-500');
                    if (errorMessageId) showErrorMessage(errorMessageId, `กรุณาเลือกอย่างน้อยหนึ่งข้อ`);
                    if (!firstInvalidElement) {
                        firstInvalidElement = container;
                    }
                    isValid = false;
                } else {
                    container.classList.remove('border-red-500');
                    if (errorMessageId) hideErrorMessage(errorMessageId);
                }
            } else {
                container.classList.remove('border-red-500');
                if (errorMessageId) hideErrorMessage(errorMessageId);
            }
        });
        if (!isValid && firstInvalidElement) {
            firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return isValid;
    }
    document.querySelectorAll('.next-step').forEach(button => {
        button.addEventListener('click', () => {
            if (validateStep(currentStep)) {
                steps[currentStep].classList.add('hidden');
                currentStep++;
                steps[currentStep].classList.remove('hidden');
                updateProgressBar();
            }
        });
    });
    document.querySelectorAll('.prev-step').forEach(button => {
        button.addEventListener('click', () => {
            steps[currentStep].classList.add('hidden');
            currentStep--;
            steps[currentStep].classList.remove('hidden');
            updateProgressBar();
        });
    });
    document.getElementById('multi-step-form').addEventListener('submit', async function(event) {
        event.preventDefault();
        let allStepsValid = true;
        let errorMessages = [];
        const proposalFile = document.querySelector('input[name="proposal_file"]');
        const additionalFile = document.querySelector('input[name="additional_file"]');
        if (!proposalFile.files || proposalFile.files.length === 0) {
            errorMessages.push('กรุณาแนบไฟล์ข้อเสนอโครงการวิจัย (Step 1)');
            allStepsValid = false;
        } else if (proposalFile.files[0].type !== 'application/pdf') {
            errorMessages.push('ไฟล์ข้อเสนอโครงการวิจัยต้องเป็นไฟล์ PDF เท่านั้น (Step 1)');
            allStepsValid = false;
        }
        if (additionalFile.files && additionalFile.files.length > 0) {
            if (additionalFile.files[0].type !== 'application/pdf') {
                errorMessages.push('ไฟล์เอกสารประกอบเพิ่มเติมต้องเป็นไฟล์ PDF เท่านั้น (Step 1)');
                allStepsValid = false;
            }
        }
        for (let i = 0; i < steps.length; i++) {
            if (!validateStep(i)) {
                allStepsValid = false;
                const invalidFields = steps[i].querySelectorAll('.border-red-500');
                invalidFields.forEach(field => {
                    const label = steps[i].querySelector(`label[for='${field.id}']`);
                    if (label) {
                        errorMessages.push(`- ${label.textContent.trim()}`);
                    }
                });
                break;
            }
        }
        if (!allStepsValid) {
            if (errorMessages.length > 0) {
                alert('กรุณาตรวจสอบและแก้ไขข้อมูลในช่องต่อไปนี้:\n' + errorMessages.join('\n') + '\n\n* ช่องที่มีกรอบแดง หรือมีข้อความ error ใต้ input คือจุดที่ต้องแก้ไข');
            } else {
                alert('กรุณาตรวจสอบข้อมูลให้ครบถ้วน ช่องที่มีกรอบแดง หรือมีข้อความ error ใต้ input คือจุดที่ต้องแก้ไข');
            }
            return;
        }
        document.getElementById('submit-form').disabled = true;
        try {
            this.submit();
        } catch (error) {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการส่งฟอร์ม กรุณาลองใหม่อีกครั้ง');
            document.getElementById('submit-form').disabled = false;
        }
    });
    document.querySelector('input[name="proposal_file"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const errorElement = document.getElementById('proposal_file-error');
        if (file) {
            if (file.type !== 'application/pdf') {
                errorElement.textContent = 'กรุณาเลือกไฟล์ PDF เท่านั้น';
                errorElement.classList.remove('hidden');
                e.target.value = '';
            } else {
                errorElement.classList.add('hidden');
            }
        }
    });
    document.querySelector('input[name="additional_file"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const errorElement = document.getElementById('additional_file-error');
        if (file) {
            if (file.type !== 'application/pdf') {
                errorElement.textContent = 'กรุณาเลือกไฟล์ PDF เท่านั้น';
                errorElement.classList.remove('hidden');
                e.target.value = '';
            } else {
                errorElement.classList.add('hidden');
            }
        }
    });
    updateProgressBar();
} 