@php
    $malaysianLocationsData = \App\Support\MalaysianLocations::all();
@endphp
<script>
window.__malaysianLocations = @json($malaysianLocationsData);

(function() {
    function getPostalNumberState(num) {
        if (num >= 1000 && num <= 2999) return 'Perlis';
        if (num >= 5000 && num <= 9999) return 'Kedah';
        if (num >= 10000 && num <= 14999) return 'Pulau Pinang';
        if (num >= 15000 && num <= 18999) return 'Kelantan';
        if (num >= 20000 && num <= 24999) return 'Terengganu';
        if ((num >= 39000 && num <= 39299) || (num >= 49000 && num <= 49099) || (num >= 69000 && num <= 69099) || (num >= 25000 && num <= 28999)) return 'Pahang';
        if (num >= 30000 && num <= 36999) return 'Perak';
        if (num >= 50000 && num <= 60999) return 'Wilayah Persekutuan Kuala Lumpur';
        if (num >= 62000 && num <= 62999) return 'Wilayah Persekutuan Putrajaya';
        if ((num >= 63000 && num <= 64999) || (num >= 68000 && num <= 68199) || (num >= 40000 && num <= 48999)) return 'Selangor';
        if (num >= 70000 && num <= 73999) return 'Negeri Sembilan';
        if (num >= 75000 && num <= 78999) return 'Melaka';
        if (num >= 80000 && num <= 86999) return 'Johor';
        if (num >= 87000 && num <= 87099) return 'Wilayah Persekutuan Labuan';
        if (num >= 88000 && num <= 91999) return 'Sabah';
        if (num >= 93000 && num <= 98999) return 'Sarawak';
        return null;
    }

    // Build fast postcode-to-location lookup map
    const postcodeLookup = {};
    Object.entries(window.__malaysianLocations).forEach(([state, data]) => {
        if (data.city_postcodes) {
            Object.entries(data.city_postcodes).forEach(([city, postcodes]) => {
                postcodes.forEach(pc => {
                    if (!postcodeLookup[pc]) {
                        postcodeLookup[pc] = { state, city };
                    }
                });
            });
        }
        if (data.postcodes) {
            const defaultCity = (data.cities && data.cities[0]) ? data.cities[0] : '';
            data.postcodes.forEach(pc => {
                if (!postcodeLookup[pc]) {
                    postcodeLookup[pc] = { state, city: defaultCity };
                }
            });
        }
    });

    window.attachMalaysiaLocationSync = function(options) {
        options = options || {};
        const stateSelect = typeof options.stateSelect === 'string'
            ? document.getElementById(options.stateSelect)
            : (options.stateSelect || document.getElementById('stateSelect'));
        const citySelect = typeof options.citySelect === 'string'
            ? document.getElementById(options.citySelect)
            : (options.citySelect || document.getElementById('citySelect'));
        const postcodeSelect = typeof options.postcodeSelect === 'string'
            ? document.getElementById(options.postcodeSelect)
            : (options.postcodeSelect || document.getElementById('postcodeSelect'));

        if (!stateSelect || !citySelect || !postcodeSelect) {
            return null;
        }

        const customInput = typeof options.customInput === 'string'
            ? document.getElementById(options.customInput)
            : (options.customInput || null);
        const customInputWrapper = typeof options.customInputWrapper === 'string'
            ? document.getElementById(options.customInputWrapper)
            : (options.customInputWrapper || null);
        const selectWrapper = typeof options.selectWrapper === 'string'
            ? document.getElementById(options.selectWrapper)
            : (options.selectWrapper || null);
        const toggleBtn = typeof options.toggleBtn === 'string'
            ? document.getElementById(options.toggleBtn)
            : (options.toggleBtn || null);
        const cancelBtn = typeof options.cancelBtn === 'string'
            ? document.getElementById(options.cancelBtn)
            : (options.cancelBtn || null);
        const allowCustom = options.allowCustom !== undefined
            ? Boolean(options.allowCustom)
            : Boolean(customInput);

        // If controller already exists on this postcodeSelect, reset its values cleanly
        if (postcodeSelect._syncController) {
            postcodeSelect._syncController.resetValues(options.initialState, options.initialCity, options.initialPostcode);
            return postcodeSelect._syncController;
        }

        const locations = window.__malaysianLocations;
        let isSyncing = false;
        let isCustomModeActive = false;

        function setCustomMode(isCustom) {
            isCustomModeActive = Boolean(isCustom);
            if (customInputWrapper && selectWrapper) {
                if (isCustomModeActive) {
                    selectWrapper.style.display = 'none';
                    customInputWrapper.style.display = 'block';
                    if (toggleBtn) {
                        toggleBtn.textContent = '← Select from list';
                    }
                    if (customInput) {
                        customInput.disabled = false;
                        customInput.setAttribute('name', 'poskod');
                        if (postcodeSelect.value && postcodeSelect.value !== '__custom__') {
                            customInput.value = postcodeSelect.value;
                        }
                        setTimeout(() => {
                            customInput.focus();
                        }, 50);
                    }
                    postcodeSelect.disabled = true;
                    postcodeSelect.removeAttribute('name');
                } else {
                    customInputWrapper.style.display = 'none';
                    selectWrapper.style.display = '';
                    if (toggleBtn) {
                        toggleBtn.textContent = '+ Add new postcode';
                    }
                    postcodeSelect.disabled = false;
                    postcodeSelect.setAttribute('name', 'poskod');
                    if (customInput) {
                        customInput.disabled = true;
                        customInput.removeAttribute('name');
                        const typed = customInput.value.trim();
                        if (typed) {
                            if (!Array.from(postcodeSelect.options).some(o => o.value === typed)) {
                                const opt = document.createElement('option');
                                opt.value = typed;
                                opt.textContent = typed;
                                postcodeSelect.appendChild(opt);
                            }
                            postcodeSelect.value = typed;
                        } else if (postcodeSelect.value === '__custom__') {
                            postcodeSelect.value = '';
                        }
                    }
                }
            }
        }

        function renderCities(stateName, targetValue) {
            const previousVal = targetValue !== undefined ? targetValue : citySelect.value;
            citySelect.innerHTML = '<option value="">Select city</option>';

            const items = [];
            if (stateName && locations[stateName]) {
                locations[stateName].cities.forEach(c => {
                    items.push({ value: c, label: c });
                });
            } else {
                const seen = new Set();
                Object.entries(locations).forEach(([st, d]) => {
                    d.cities.forEach(c => {
                        if (!seen.has(c)) {
                            seen.add(c);
                            items.push({ value: c, label: `${c} (${st})` });
                        }
                    });
                });
                items.sort((a, b) => a.value.localeCompare(b.value));
            }

            items.forEach(it => {
                const opt = document.createElement('option');
                opt.value = it.value;
                opt.textContent = it.label;
                if (it.value === previousVal) {
                    opt.selected = true;
                }
                citySelect.appendChild(opt);
            });

            // If a previous custom value is not in the list, preserve it
            if (previousVal && !Array.from(citySelect.options).some(o => o.value === previousVal)) {
                const customOpt = document.createElement('option');
                customOpt.value = previousVal;
                customOpt.textContent = previousVal;
                customOpt.selected = true;
                citySelect.appendChild(customOpt);
            }

            if (previousVal) {
                citySelect.value = previousVal;
            }
        }

        function renderPostcodes(stateName, cityName, targetValue) {
            const previousVal = targetValue !== undefined
                ? targetValue
                : (isCustomModeActive && customInput ? customInput.value : postcodeSelect.value);

            postcodeSelect.innerHTML = '<option value="">Select postcode</option>';

            if (allowCustom) {
                const customActionOpt = document.createElement('option');
                customActionOpt.value = '__custom__';
                customActionOpt.textContent = '+ Add new postcode...';
                postcodeSelect.appendChild(customActionOpt);
            }

            const list = [];
            if (stateName && locations[stateName]) {
                const sData = locations[stateName];
                if (cityName && sData.city_postcodes && sData.city_postcodes[cityName] && sData.city_postcodes[cityName].length > 0) {
                    list.push(...sData.city_postcodes[cityName]);
                } else {
                    list.push(...sData.postcodes);
                }
            } else {
                const seen = new Set();
                Object.values(locations).forEach(d => {
                    d.postcodes.forEach(p => {
                        if (!seen.has(p)) {
                            seen.add(p);
                            list.push(p);
                        }
                    });
                });
                list.sort();
            }

            list.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                if (p === previousVal) {
                    opt.selected = true;
                }
                postcodeSelect.appendChild(opt);
            });

            // If a previous custom value is not in the list, preserve it
            if (previousVal && previousVal !== '__custom__' && !Array.from(postcodeSelect.options).some(o => o.value === previousVal)) {
                const customOpt = document.createElement('option');
                customOpt.value = previousVal;
                customOpt.textContent = previousVal;
                customOpt.selected = true;
                postcodeSelect.appendChild(customOpt);
            }

            if (previousVal && previousVal !== '__custom__') {
                postcodeSelect.value = previousVal;
                if (customInput) {
                    customInput.value = previousVal;
                }
            }
        }

        function syncLocationFromPostcode(pc) {
            let match = postcodeLookup[pc];
            let resolvedState = match ? match.state : null;
            let resolvedCity = match ? match.city : null;

            if (!resolvedState) {
                const num = parseInt(pc, 10);
                if (!isNaN(num)) {
                    resolvedState = getPostalNumberState(num);
                    if (resolvedState && locations[resolvedState]) {
                        resolvedCity = locations[resolvedState].cities[0] || '';
                    }
                }
            }

            if (resolvedState) {
                stateSelect.value = resolvedState;
                const targetCity = resolvedCity || citySelect.value;
                renderCities(resolvedState, targetCity);
                renderPostcodes(resolvedState, targetCity, pc);
                if (targetCity) {
                    citySelect.value = targetCity;
                }
            }
        }

        function handlePostcodeChange() {
            if (isSyncing) return;
            const pc = postcodeSelect.value.trim();
            if (pc === '__custom__') {
                setCustomMode(true);
                return;
            }
            if (customInput) {
                customInput.value = pc;
            }
            isSyncing = true;
            try {
                if (!pc) return;
                syncLocationFromPostcode(pc);
            } finally {
                isSyncing = false;
            }
        }

        function handleCityChange() {
            if (isSyncing) return;
            isSyncing = true;
            try {
                const chosenCity = citySelect.value.trim();
                const curState = stateSelect.value;
                const curPostcode = isCustomModeActive && customInput ? customInput.value : postcodeSelect.value;

                if (!chosenCity) {
                    if (curState) {
                        renderPostcodes(curState, '', curPostcode);
                    }
                    return;
                }

                let targetState = curState;
                if (!targetState || !locations[targetState] || !locations[targetState].cities.includes(chosenCity)) {
                    for (const [st, d] of Object.entries(locations)) {
                        if (d.cities.includes(chosenCity)) {
                            targetState = st;
                            stateSelect.value = st;
                            break;
                        }
                    }
                }

                if (targetState) {
                    renderCities(targetState, chosenCity);
                    renderPostcodes(targetState, chosenCity, curPostcode);
                }
            } finally {
                isSyncing = false;
            }
        }

        function handleStateChange() {
            if (isSyncing) return;
            isSyncing = true;
            try {
                const selectedState = stateSelect.value.trim();
                const curCity = citySelect.value;
                const curPostcode = isCustomModeActive && customInput ? customInput.value : postcodeSelect.value;

                if (selectedState && locations[selectedState]) {
                    const sData = locations[selectedState];
                    // Keep currently selected city/postcode if they belong to this state, preventing unload!
                    const keepCity = curCity && sData.cities.includes(curCity) ? curCity : '';
                    const keepPostcode = curPostcode && sData.postcodes.includes(curPostcode) ? curPostcode : '';

                    renderCities(selectedState, keepCity);
                    renderPostcodes(selectedState, keepCity, keepPostcode);
                } else {
                    renderCities('', curCity);
                    renderPostcodes('', curCity, curPostcode);
                }
            } finally {
                isSyncing = false;
            }
        }

        function resetValues(rawInitialState, rawInitialCity, rawInitialPostcode) {
            let resolvedState = rawInitialState || '';
            let initialCity = rawInitialCity || '';
            let initialPostcode = rawInitialPostcode || '';

            if (!resolvedState && initialPostcode) {
                const match = postcodeLookup[initialPostcode];
                if (match) {
                    resolvedState = match.state;
                    if (!initialCity) initialCity = match.city;
                } else {
                    const num = parseInt(initialPostcode, 10);
                    if (!isNaN(num)) {
                        resolvedState = getPostalNumberState(num) || '';
                    }
                }
            }
            if (!resolvedState && initialCity) {
                for (const [st, d] of Object.entries(locations)) {
                    if (d.cities.includes(initialCity)) {
                        resolvedState = st;
                        break;
                    }
                }
            }

            stateSelect.value = resolvedState || '';
            renderCities(resolvedState, initialCity);
            renderPostcodes(resolvedState, initialCity, initialPostcode);
            setCustomMode(false);
            if (customInput) {
                customInput.value = initialPostcode || '';
            }
        }

        // Initialize State Options
        const rawInitialState = options.initialState || stateSelect.value || '';
        let initialCity = options.initialCity || citySelect.value || '';
        let initialPostcode = options.initialPostcode || postcodeSelect.value || '';
        let resolvedInitialState = rawInitialState;

        // Auto-resolve state if postcode or city provided but state empty
        if (!resolvedInitialState && initialPostcode) {
            const match = postcodeLookup[initialPostcode];
            if (match) {
                resolvedInitialState = match.state;
                if (!initialCity) initialCity = match.city;
            } else {
                const num = parseInt(initialPostcode, 10);
                if (!isNaN(num)) {
                    resolvedInitialState = getPostalNumberState(num) || '';
                }
            }
        }
        if (!resolvedInitialState && initialCity) {
            for (const [st, d] of Object.entries(locations)) {
                if (d.cities.includes(initialCity)) {
                    resolvedInitialState = st;
                    break;
                }
            }
        }

        stateSelect.innerHTML = '<option value="">Select state</option>';
        Object.keys(locations).sort().forEach(st => {
            const opt = document.createElement('option');
            opt.value = st;
            opt.textContent = st;
            if (st === resolvedInitialState) {
                opt.selected = true;
            }
            stateSelect.appendChild(opt);
        });

        if (resolvedInitialState) {
            stateSelect.value = resolvedInitialState;
        }

        renderCities(resolvedInitialState, initialCity);
        renderPostcodes(resolvedInitialState, initialCity, initialPostcode);

        // Bind change events
        postcodeSelect.addEventListener('change', handlePostcodeChange);
        citySelect.addEventListener('change', handleCityChange);
        stateSelect.addEventListener('change', handleStateChange);

        if (customInput) {
            customInput.addEventListener('input', function() {
                const pc = customInput.value.trim();
                if (pc.length === 5 && !isNaN(parseInt(pc, 10))) {
                    isSyncing = true;
                    try {
                        syncLocationFromPostcode(pc);
                    } finally {
                        isSyncing = false;
                    }
                }
            });
            customInput.addEventListener('change', function() {
                const pc = customInput.value.trim();
                if (pc) {
                    isSyncing = true;
                    try {
                        syncLocationFromPostcode(pc);
                    } finally {
                        isSyncing = false;
                    }
                }
            });
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setCustomMode(!isCustomModeActive);
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setCustomMode(false);
            });
        }

        const controller = {
            resetValues: resetValues,
            setCustomMode: setCustomMode,
            renderCities: renderCities,
            renderPostcodes: renderPostcodes
        };
        postcodeSelect._syncController = controller;
        return controller;
    };
})();
</script>
