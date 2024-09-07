/**
 * Find the word currently being typed in the given element
 *
 * @param {HTMLInputElement} element
 * @returns {string}
 */
function getCurrentWord(element) {
	let text = element.value;
	let pos = element.selectionStart;
	var start = text.lastIndexOf(' ', pos-1);
	if(start === -1) {
		start = 0;
	}
	else {
		start++; // skip the space
	}
	return text.substring(start, pos);
}

/**
 * Whenever input changes, look at what word is currently
 * being typed, and fetch completions for it.
 *
 * @param {HTMLInputElement} element
 */
function updateCompletions(element) {
	// Reset selction, but no need to validate and re-render
	// highlightCompletion(element, -1);
	element.selected_completion = -1;

	// get the word before the cursor
	var word = getCurrentWord(element);

	// search for completions
	if(element.completer_timeout !== null) {
		clearTimeout(element.completer_timeout);
		element.completer_timeout = null;
	}
	if(word === '' || word === '-') {
		element.completions = {};
		renderCompletions(element);
	}
	else {
		element.completer_timeout = setTimeout(() => {
			const wordWithoutMinus = word.replace(/^-/, '');
			fetch(shm_make_link('api/internal/autocomplete', {s: wordWithoutMinus})).then(
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

	// remove any existing completion block
	hideCompletions();

	// if there are no completions, don't render anything
	if(Object.keys(completions).length === 0) {
		return;
	}

	let completions_el = document.createElement('ul');
	completions_el.className = 'autocomplete_completions';
	completions_el.id = 'completions';

	// add children for top completions, with the selected one highlighted
	let word = getCurrentWord(element);
	Object.keys(completions).filter(
		(key) => {
			let k = key.toLowerCase();
			let w = word.replace(/^-/, '').toLowerCase();
			return (k.startsWith(w) || k.split(':').some((k) => k.startsWith(w)))
		}
	).slice(0, 100).forEach((key, i) => {
		let li = document.createElement('li');
		li.innerText = completions[key].newtag ?
			`${key} → ${completions[key].newtag} (${completions[key].count})` :
			`${key} (${completions[key].count})` ;
		if(i === selected_completion) {
			li.className = 'selected';
		}
		// on hover, select the completion
		// (use mousemove rather than mouseover, because
		// if the mouse is stationary, then we want the
		// keyboard to be able to override it)
		li.addEventListener('mousemove', () => {
			// avoid re-rendering if the completion is already selected
			if(element.selected_completion !== i) {
				highlightCompletion(element, i);
			}
		});
		// on click, set the completion
		// (mousedown is used instead of click because click is
		// fired after blur, which causes the completion block to
		// be removed before the click event is handled)
		li.addEventListener('mousedown', (event) => {
			setCompletion(element, key);
			event.preventDefault();
		});
		li.addEventListener('touchstart', (event) => {
			setCompletion(element, key);
			event.preventDefault();
		});
		completions_el.appendChild(li);
	});

	// insert the completion block after the element
	if(element.parentNode) {
		element.parentNode.insertBefore(completions_el, element.nextSibling);
		let br = element.getBoundingClientRect();
		completions_el.style.minWidth = br.width + 'px';
		completions_el.style.maxWidth = 'calc(100vw - 2rem - ' + br.left + 'px)';
		completions_el.style.left = window.scrollX + br.left + 'px';
		completions_el.style.top = window.scrollY + (br.top + br.height) + 'px';
	}
}

/**
 * hide the completions block
 */
function hideCompletions() {
	document.querySelectorAll('.autocomplete_completions').forEach((element) => {
		element.remove();
	});
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

	// resolve alias before setting the word
	if(element.completions[new_word].newtag) {
		new_word = element.completions[new_word].newtag;
	}

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
	if(text[start] === '-') {
		new_word = '-' + new_word;
	}
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

		// safari treats spellcheck as a form of autocomplete
		element.setAttribute('spellcheck', 'off');
	
		// when element is focused, add completion block
		element.addEventListener('focus', () => {
			updateCompletions(element);
		});

		// when element is blurred, remove completion block
		element.addEventListener('blur', () => {
			hideCompletions();
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
			else if(event.code === "ArrowDown") {
				event.preventDefault();
				highlightCompletion(element, element.selected_completion+1);
			}
			// if enter or right are pressed while a completion is selected, add the selected completion
			else if((event.code === "Enter" || event.code == "ArrowRight") && element.selected_completion !== -1) {
				event.preventDefault();
				const key = Object.keys(element.completions)[element.selected_completion]
				setCompletion(element, key);
			}
			// if escape is pressed, hide the completion block
			else if(event.code === "Escape") {
				event.preventDefault();
				hideCompletions();
			}
		});

		// on change, update completions
		element.addEventListener('input', () => {
			updateCompletions(element);
		});
	});
});
