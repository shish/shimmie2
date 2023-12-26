let completions_el = document.createElement('ul');
completions_el.className = 'autocomplete_completions';
completions_el.id = 'completions';

/**
 * Whenever input changes, look at what word is currently
 * being typed, and fetch completions for it.
 *
 * @param {HTMLInputElement} element
 */
function updateCompletions(element) {
	highlightCompletion(element, -1);

	let text = element.value;
	let pos = element.selectionStart;

	// get the word before the cursor
	var start = text.lastIndexOf(' ', pos-1);
	if(start === -1) {
		start = 0;
	}
	else {
		start++; // skip the space
	}
	var word = text.substring(start, pos);

	// search for completions
	if(element.completer_timeout !== null) {
		clearTimeout(element.completer_timeout);
		element.completer_timeout = null;
	}
	if(word === '') {
		element.completions = {};
		renderCompletions(element);
	}
	else {
		element.completer_timeout = setTimeout(() => {
			fetch(base_href + '/api/internal/autocomplete?s=' + word).then(
				(response) => response.json()
			).then((json) => {
				if(element.selected_completion !== -1) {
					return; // user has started to navigate the completions, so don't update them
				}
				element.completions = json;
				renderCompletions(element);
			});
		}, 250);
		renderCompletions(element);	
	}
}

/**
 * Highlight the nth completion
 *
 * @param {HTMLInputElement} element
 * @param {number} n
 */
function highlightCompletion(element, n) {
	if(!element.completions) return;
	element.selected_completion = Math.min(
		Math.max(n, -1),
		Object.keys(element.completions).length-1
	);
	renderCompletions(element);
}

/**
 * Render the completion block
 *
 * @param {HTMLInputElement} element
 */
function renderCompletions(element) {
	let completions = element.completions;
	let selected_completion = element.selected_completion;

	// if there are no completions, remove the completion block
	if(Object.keys(completions).length === 0) {
		completions_el.remove();
		return;
	}

	// remove all children
	while(completions_el.firstChild) {
		completions_el.removeChild(completions_el.firstChild);
	}

	// add children for each completion, with the selected one highlighted
	Object.keys(completions).forEach((key, i) => {
		let value = completions[key];

		let li = document.createElement('li');
		li.innerHTML = key + ' (' + value + ')';
		if(i === selected_completion) {
			li.className = 'selected';
		}
		// on hover, select the completion
		li.addEventListener('mouseover', () => {
			highlightCompletion(element, i);
		});
		// on click, set the completion
		// (mousedown is used instead of click because click is
		// fired after blur, which causes the completion block to
		// be removed before the click event is handled)
		li.addEventListener('mousedown', (event) => {
			setCompletion(element, key);
			event.preventDefault();
		});
		completions_el.appendChild(li);
	});

	// insert the completion block after the element
	if(element.parentNode) {
		element.parentNode.insertBefore(completions_el, element.nextSibling);
		let br = element.getBoundingClientRect();
		completions_el.style.width = br.width + 'px';
		completions_el.style.left = window.scrollX + br.left + 'px';
		completions_el.style.top = window.scrollY + (br.top + br.height) + 'px';
	}
}
/**
 * Set the current word to the given completion
 *
 * @param {HTMLInputElement} element
 * @param {string} new_word
 */
function setCompletion(element, new_word) {
	let text = element.value;
	let pos = element.selectionStart;

	// get the word before the cursor
	var start = text.lastIndexOf(' ', pos-1);
	if(start === -1) {
		start = 0;
	}
	else {
		start++; // skip the space
	}
	var end = text.indexOf(' ', pos);
	if(end === -1) {
		end = text.length;
	}

	// replace the word with the completion
	new_word += ' ';
	element.value = text.substring(0, start) + new_word + text.substring(end);
	element.selectionStart = start + new_word.length;
	element.selectionEnd = start + new_word.length;

	// reset metadata
	element.completions = {};
	element.selected_completion = -1;
	if(element.completer_timeout) {
		clearTimeout(element.completer_timeout);
		element.completer_timeout = null;
	}
}

document.addEventListener('DOMContentLoaded', () => {
	// Find all elements with class 'autocomplete_tags'
	document.querySelectorAll('.autocomplete_tags').forEach((element) => {
		// set metadata
		element.completions = {};
		element.selected_completion = -1;
		element.completer_timeout = null;

		// disable built-in autocomplete
		element.setAttribute('autocomplete', 'off');
	
		// when element is focused, add completion block
		element.addEventListener('focus', () => {
			updateCompletions(element);
		});

		// when element is blurred, remove completion block
		element.addEventListener('blur', () => {
			// if we are blurring because we are clicking on a completion,
			// don't remove the completion block until the click event is done
			completions_el.remove();
		});

		// when cursor is moved, change current completion
		document.addEventListener('selectionchange', () => {
			// if element is focused
			if(document.activeElement === element) {
				updateCompletions(element);
			}
		});

		element.addEventListener('keydown', (event) => {
			// up / down should select previous / next completion
			if(event.code === "ArrowUp") {
				event.preventDefault();
				highlightCompletion(element, element.selected_completion-1);
			}
			if(event.code === "ArrowDown") {
				event.preventDefault();
				highlightCompletion(element, element.selected_completion+1);
			}
			// if enter is pressed, add the selected completion
			if(event.code === "Enter" && element.selected_completion !== -1) {
				event.preventDefault();
				setCompletion(element, Object.keys(element.completions)[element.selected_completion]);
			}
			// if escape is pressed, hide the completion block
			if(event.code === "Escape") {
				completions_el.remove();
			}
		});

		// on change, update completions
		element.addEventListener('input', () => {
			updateCompletions(element);
		});
	});
});
