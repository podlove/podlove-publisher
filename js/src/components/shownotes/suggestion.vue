<template>
<div class="e-suggestion">
  <div class="e-suggestion-wrapper">
    <div class="e-suggestion-content">
      <span>Suggestion</span>
      <span>{{ title }}</span>            
    </div>
    <div class="e-suggestion-action">
      <div class="e-suggestion-action-wrapper" @click.prevent="$emit('accept')">
        <span>ok</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check"><polyline points="20 6 9 17 4 12"></polyline></svg>
      </div>
    </div>
  </div>
</div>
</template>

<script>
export default {
  props: ["title"]
};
</script>

<style>
.e-suggestion-wrapper {
  display: flex;
  justify-content: space-between;
  background-color: #fffff0;
  border: 1px solid #faf089;
  color: #744210;
  padding: 8px;
  margin-top: 2px;
}

.e-suggestion-content {
  display: flex;
  flex-direction: column;
}

.e-suggestion-content span:last-child {
  font-weight: bold;
}

.e-suggestion-action-wrapper {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.e-suggestion-action-wrapper span {
  margin-right: 4px;
}
</style>
