import axios from 'axios';

export const toggleResetCap = async (active, userId, leadAllocationId) => {
  await axios
    .post('/lead-allocation/toggle-reset-cap', {
      leadId: leadAllocationId,
      userId,
      resetCap: active,
    })
    .catch(() => {
      throw error;
    });
};

export const statusSubmit = async (userId, id, reason, quoteType) => {
  await axios
    .post(`/lead-allocation/${quoteType}/update-availability`, [
      {
        userId,
        id,
        reason,
      },
    ])
    .catch(() => {
      throw error;
    });
};

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
};
