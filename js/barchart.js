        // Pass the loan counts from PHP to JavaScript
        // uncomment getting data from PHP
        const loanCounts = <?php echo json_encode($loanCounts); ?>;  

        // Get the canvas element and context
        const barCanvas = document.getElementById('barChart');
        const barCtx = barCanvas.getContext('2d');

        // Define all loan types
        const loanTypes = Object.keys(loanCounts);
        const counts = Object.values(loanCounts);

        // Abbreviate labels (first 2 letters)
        const abbreviatedLabels = loanTypes.map(label => label.substring(0, 2).toUpperCase());

        // Define chart dimensions
        const barWidth = 30; // Width of each bar
        const barSpacing = 20; // Spacing between bars
        const startX = 50; // Starting X position for the first bar (reduced margin)
        const startY = barCanvas.height - 80; // Starting Y position (bottom of the chart)
        const axisPadding = 5; // Reduced padding for the Y-axis

        // Calculate the maximum value for the Y-axis scale
        const maxCount = Math.max(...counts);
        const yAxisMax = Math.ceil(maxCount / 5) * 5; // Round up to the nearest multiple of 5

        // Draw the bars
        counts.forEach((value, index) => {
            const x = startX + (barWidth + barSpacing) * index;
            const y = startY - (value / yAxisMax) * (startY - 20); // Scale bar height to fit Y-axis
            barCtx.fillStyle = '#74C0FC'; // Bar color
            barCtx.fillRect(x, y, barWidth, startY - y); // Draw the bar
        });

        // Draw the X-axis labels (abbreviated)
        barCtx.fillStyle = 'white'; // Label color
        barCtx.font = '14px Trebuchet MS'; // Label font
        barCtx.textAlign = 'center'; // Center-align the text
        abbreviatedLabels.forEach((label, index) => {
            const x = startX + (barWidth + barSpacing) * index + barWidth / 2;
            barCtx.fillText(label, x, startY + 20); // Draw the label below the bar
        });

        // Draw the Y-axis
        barCtx.beginPath();
        barCtx.moveTo(startX - axisPadding, startY);
        barCtx.lineTo(startX - axisPadding, 20);
        barCtx.strokeStyle = 'white'; // Y-axis color
        barCtx.stroke();

        // Draw Y-axis labels and grid lines (steps of 2 for better readability)
        barCtx.fillStyle = 'whitesmoke';
        barCtx.font = '14px Trebuchet MS';
        barCtx.textAlign = 'right'; // Right-align Y-axis labels
        barCtx.strokeStyle = 'rgba(255, 255, 255, 0.2)'; // Grid line color

        for (let i = 0; i <= yAxisMax; i += 2) { // Steps of 2 for better readability
            const y = startY - (i / yAxisMax) * (startY - 20); // Scale Y-axis labels

            // Draw Y-axis labels
            barCtx.fillText(i, startX - axisPadding - 5, y + 5);

            // Draw horizontal grid lines
            barCtx.beginPath();
            barCtx.moveTo(startX - axisPadding, y);
            barCtx.lineTo(barCanvas.width - 250, y); // Extend grid line across the chart
            barCtx.stroke();
        }

        // Draw the legend (key) on the side
        const legendX = barCanvas.width - 250; // X position for the legend
        const legendY = 40; // Y position for the legend
        const legendSpacing = 20; // Spacing between legend items

        barCtx.font = '16px Trebuchet MS';
        barCtx.textAlign = 'left'; // Left-align legend text
        loanTypes.forEach((label, index) => {
            

            // Draw the label text
            barCtx.fillStyle = 'lightgray';
            barCtx.fillText(`${abbreviatedLabels[index]}: ${label}`, legendX + 20, legendY + index * legendSpacing + 12);
        });