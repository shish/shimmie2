document.addEventListener("DOMContentLoaded", () => {
	const checkbox = document.getElementById("nice_urls");
	if (checkbox !== null) {
		// Remove the TR that contains "Javascript disabled, can't test nice URLs"
		checkbox.parentElement.parentElement.nextSibling.remove();
		const out_span = document.createElement("span");
		checkbox.parentNode.appendChild(out_span);

		if (out_span !== null) {
			checkbox.disabled = true;
			out_span.innerHTML = "(testing...)";
			const test_url =
				document.body.getAttribute("data-base-href") + "/nicetest";
			console.log("NiceURL testing with", test_url);

			fetch(test_url)
				.then((response) => {
					if (!response.ok) {
						checkbox.disabled = true;
						out_span.innerHTML = "(http error)";
						console.log(
							"NiceURL test got HTTP error:",
							response.status,
							response.statusText,
						);
					} else {
						response.text().then((text) => {
							if (text === "ok") {
								checkbox.disabled = false;
								out_span.innerHTML =
									"(test passed - it is recommended to have them enabled)";
								console.log("NiceURL test passed");
							} else {
								checkbox.disabled = true;
								out_span.innerHTML = "(test failed)";
								console.log(
									"NiceURL test got wrong content:",
									text,
								);
							}
						});
					}
				})
				.catch((e) => {
					checkbox.disabled = true;
					out_span.innerHTML = "(request failed)";
					console.log("NiceURL test hit an exception:", e);
				});
		}
	}
});
