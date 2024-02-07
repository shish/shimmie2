document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.getElementById('nice_urls');
    const out_span = document.getElementById('nicetest');
    
    if(checkbox !== null && out_span !== null) {
        checkbox.disabled = true;
        out_span.innerHTML = '(testing...)';

        fetch(document.body.getAttribute('data-base-href') + "/nicetest").then(response => {
            if(!response.ok) {
                checkbox.disabled = true;
                out_span.innerHTML = '(http error)';
            } else {
                response.text().then(text => {
                    if(text === 'ok') {
                        checkbox.disabled = false;
                        out_span.innerHTML = '(test passed)';
                    } else {
                        checkbox.disabled = true;
                        out_span.innerHTML = '(test failed)';
                    }
                });
            }
        }).catch(() => {
            checkbox.disabled = true;
            out_span.innerHTML = '(request failed)';
        });
    }
});
