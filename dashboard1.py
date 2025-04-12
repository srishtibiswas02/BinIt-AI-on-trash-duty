import folium
import matplotlib.pyplot as plt
from io import BytesIO
import base64

# Sample data for New Delhi areas
waste_data = {
    "Connaught Place": [50, 30, 20],
    "Chandni Chowk": [60, 25, 15],
    "Lajpat Nagar": [40, 35, 25],
}
waste_labels = ["Plastic", "Organic", "Metal"]

# Coordinates for New Delhi areas
locations = {
    "Connaught Place": [28.6315, 77.2167],
    "Chandni Chowk": [28.6562, 77.2301],
    "Lajpat Nagar": [28.5672, 77.2432],
}

# Create map centered around New Delhi with limited boundaries
m = folium.Map(
    location=[28.6139, 77.209], 
    zoom_start=13, 
    # tiles="CartoDB positron",  # A clean, focused tile map
    control_scale=True
)

def generate_chart(area):
    fig, ax = plt.subplots()
    ax.bar(waste_labels, waste_data[area], color=["blue", "green", "orange"])
    ax.set_title(f"Waste Data for {area}")
    ax.set_ylabel("Quantity")
    
    buf = BytesIO()
    plt.savefig(buf, format="png")
    plt.close(fig)
    return base64.b64encode(buf.getvalue()).decode("utf-8")

# Add markers for New Delhi areas
for area, coords in locations.items():
    img = generate_chart(area)
    html = f'<img src="data:image/png;base64,{img}">'
    iframe = folium.IFrame(html, width=400, height=300)
    popup = folium.Popup(iframe, max_width=500)
    folium.CircleMarker(
        location=coords,
        radius=10,
        color='red',
        fill=True,
        fill_color='red',
        popup=popup
    ).add_to(m)

# Save the map to an HTML file
m.save("new_delhi_waste_dashboard.html")
print("Dashboard created: Open 'new_delhi_waste_dashboard.html'")
