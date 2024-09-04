<template>
  <div v-if="visible" class="custom-notification" ref="customNotification">
    <div class="notification-content">
      <span class="close-icon closeTag" @click="closeNotification"
        >&times;</span
      >
      <img
        :src="imageUrl"
        alt="Notification Image"
        class="notification-image"
      />
      <div>
        <h2 class="notification-title">{{ title }}</h2>
        <p class="notification-message">
          {{ message }}
          <a class="notification-button" @click="openUrl"
            >{{ quoteType }}-{{ uuid }}</a
          >
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, getCurrentInstance } from 'vue';
const instance = getCurrentInstance();

const props = defineProps({
  imageUrl: String,
  title: String,
  message: String,
  url: String,
  timeout: Number,
  callHideFunction: Function,
  uuid: String,
  quoteType: String,
});

const visible = ref(false);

const closeNotification = () => {
  console.log('instance.parent', instance.parent);
  props.callHideFunction();

  // visible.value = false;
};

const showNotification = () => {
  visible.value = true;
  if (props.timeout) {
    setTimeout(() => {
      visible.value = false;
    }, props.timeout);
  }
};

const openUrl = () => {
  window.open(props.url, '_self');
};

onMounted(() => {
  showNotification();
});
</script>

<style scoped>
.custom-notification {
  background-color: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  max-width: 700px;
  margin: 0 auto;
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}

.closeTag {
  position: absolute;
  right: 0;
  top: -12px;
  font-size: 22px;
  cursor: pointer;
}

.notification-content {
  display: flex;
  align-items: center;
  position: relative;
}

.notification-image {
  width: 52px;
  height: auto;
  border-radius: 8px;
  max-width: 300px;
  margin-right: 20px;
}

.notification-title {
  color: #007bff;
  font-size: 1.2em;
}

.notification-message {
  color: #000;
  margin-bottom: 0px;
}

.notification-button {
  color: #007bff;
  cursor: pointer;
  text-decoration: underline;
}
</style>
