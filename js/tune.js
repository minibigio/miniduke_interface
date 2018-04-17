document.querySelectorAll("select.preselect").forEach(function (t) {
    t.addEventListener("change", function () {
        var tdId = this.parentNode.parentNode.parentNode.getAttribute('data-id');

        var comparator = document.querySelector('div[data-id="'+tdId+'"] select[name="comparator['+tdId+']"]');
        var low = document.querySelector('div[data-id="'+tdId+'"] input[name="low['+tdId+']"]');
        var high = document.querySelector('div[data-id="'+tdId+'"] input[name="high['+tdId+']"]');

        comparator.value = t.selectedOptions[0].getAttribute('data-comparator');
        low.value = t.selectedOptions[0].getAttribute('data-low');
        high.value = t.selectedOptions[0].getAttribute('data-high');
    });
});

document.querySelectorAll('.tune input').forEach(function (t) {
   t.addEventListener("keydown", function () {
       console.log(this.getAttribute('data-id'));
       var tdId = this.getAttribute('data-id');
       document.querySelector('select[data-id="'+tdId+'"] [value="custom"]').selected = true;
   })
});
