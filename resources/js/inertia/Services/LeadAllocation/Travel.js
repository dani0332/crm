import axios from "axios";

export const toggleNormalAllocation = async (active, userId, laId) => {
    await axios
    .post('/lead-allocation/toggle-normal-allocation', {
      laId,
      userId,
      nlStatus: active,
    })
    .catch(() => {
      throw error;
    });
}

export const toggleResetCap = async (active, userId, leadId) => {
  await axios
  .post('/lead-allocation/toggle-reset-cap', {
    leadId,
    userId,
    resetCap: active,
  })
  .catch((error) => {
    throw error;
  });
}

export const toggleBlStatus = async (active, userId, leadId) => {
  await axios
  .post('/lead-allocation/toggle-bl-status', {
    leadId,
    userId,
    buyLeadStatus: active,
  })
  .catch(error => {
    throw error;
  });
}

export const toggleBLResetCap = async (active, userId, laId) => {
  await axios
    .post('/lead-allocation/toggle-bl-reset-cap', {
      laId,
      userId,
      blResetCap: active,
    })
    .catch((error) => {
      throw error;
    });
}

export const statusSubmit = async (quoteType, data) => {
  await axios
  .post(`/lead-allocation/${quoteType}/update-availability`, data)
  .catch((error) => {
    throw error;
  });
}

export const toggleHardStop = async (status, userId) => {
  await axios.post(
    '/travel-lead-allocation/update-hard-stop', {
      userId: userId,
      status: status,
    })
    .catch((error) => {
      throw error;
    });
}