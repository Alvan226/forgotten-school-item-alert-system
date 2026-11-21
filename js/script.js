// Small front-end interactions for the demo version.
// Forms currently show a message instead of saving data because no database is connected.
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-demo-form]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      alert("This page design is ready. Form processing will be added when the PHP and database are connected.");
    });
  });
});
