    // Get the canvas element and context
    const pieCanvas = document.getElementById('pieChart');
    const pieCtx = pieCanvas.getContext('2d');

    // Define dummy data
    const pieData = {
        labels: ['Pending', 'Disbursed', 'Rejected'],
        values: [50, 30, 20], // Percentages for each status
    };

    // Define colors for each status
    const statusColors = {
        'Pending': 'white', 
        'Disbursed': 'lightgreen', 
        'Rejected': 'tomato',
    };

    // Extract labels and values from pieData
    const labels = pieData.labels;
    const values = pieData.values;

    // Calculate the total for percentage calculations
    const total = values.reduce((sum, value) => sum + value, 0);

    // Draw the pie chart
    let startAngle = 0;
    const centerX = pieCanvas.width / 3;
    const centerY = pieCanvas.height / 2;
    const radius = 80;

    values.forEach((value, index) => {
        const sliceAngle = (2 * Math.PI * value) / total;
        pieCtx.beginPath();
        pieCtx.moveTo(centerX, centerY);
        pieCtx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        pieCtx.closePath();
        pieCtx.fillStyle = statusColors[labels[index]]; // Use color based on status
        pieCtx.fill();
        startAngle += sliceAngle;
    });

    // Add a legend
    pieCtx.font = '14px Trebuchet MS';
    values.forEach((value, index) => {
        const percentage = ((value / total) * 100).toFixed(2);
        pieCtx.fillStyle = statusColors[labels[index]];
        pieCtx.fillRect(centerX + radius + 20, 20 + index * 20, 15, 15);
        pieCtx.fillStyle = 'whitesmoke';
        pieCtx.fillText(`${labels[index]}: ${value}%`, centerX + radius + 40, 32 + index * 20);
    });