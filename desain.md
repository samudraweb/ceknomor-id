---
name: True Signal
colors:
  surface: '#f9f9f9'
  surface-dim: '#dadada'
  surface-bright: '#f9f9f9'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3f3'
  surface-container: '#eeeeee'
  surface-container-high: '#e8e8e8'
  surface-container-highest: '#e2e2e2'
  on-surface: '#1a1c1c'
  on-surface-variant: '#404754'
  inverse-surface: '#2f3131'
  inverse-on-surface: '#f1f1f1'
  outline: '#717785'
  outline-variant: '#c0c6d6'
  surface-tint: '#005eb4'
  primary: '#005bb0'
  on-primary: '#ffffff'
  primary-container: '#0074dc'
  on-primary-container: '#fefcff'
  inverse-primary: '#a8c8ff'
  secondary: '#3f5d9c'
  on-secondary: '#ffffff'
  secondary-container: '#9bb8fe'
  on-secondary-container: '#284786'
  tertiary: '#223bff'
  on-tertiary: '#ffffff'
  tertiary-container: '#4f62ff'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d5e3ff'
  primary-fixed-dim: '#a8c8ff'
  on-primary-fixed: '#001b3c'
  on-primary-fixed-variant: '#00468a'
  secondary-fixed: '#d9e2ff'
  secondary-fixed-dim: '#b0c6ff'
  on-secondary-fixed: '#001944'
  on-secondary-fixed-variant: '#254583'
  tertiary-fixed: '#dfe0ff'
  tertiary-fixed-dim: '#bdc2ff'
  on-tertiary-fixed: '#000964'
  on-tertiary-fixed-variant: '#0020dc'
  background: '#f9f9f9'
  on-background: '#1a1c1c'
  surface-variant: '#e2e2e2'
  surface-white: '#FFFFFF'
  text-deep: '#042D6B'
  action-vibrant: '#0027FF'
  background-alt: '#F2F2F2'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 56px
    fontWeight: '700'
    lineHeight: 64px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 8px
  sm: 16px
  md: 24px
  lg: 48px
  xl: 80px
  container-max: 1280px
  gutter: 24px
---

## Brand & Style
The design system is built on a foundation of reliability and clarity. It targets a global audience that values security, efficiency, and communication integrity. The personality is "The Helpful Guardian"—authoritative yet accessible, technical but human-centric.

The visual style follows a **Modern Corporate** aesthetic with a strong emphasis on **Minimalism**. It utilizes expansive white space, a high-contrast primary palette, and precise geometric shapes to convey a sense of institutional trust. The interface avoids unnecessary decorative elements, ensuring that utility and search-driven actions remain the focal point.

## Colors
The palette is dominated by a core "Trust Blue" (`#0087FF`), which provides immediate recognition and a sense of calm. 

- **Primary Blue:** Used for primary actions, search buttons, and brand iconography.
- **Deep Navy:** Utilized for high-level headlines and secondary buttons to provide a grounded, professional contrast.
- **Electric Blue:** Reserved for hover states and active indicators to inject energy into the interface.
- **Neutral/Background:** Pure white (`#FFFFFF`) is the primary canvas, while light gray (`#F2F2F2`) is used for section containers and input field backgrounds to maintain a clean hierarchy.

## Typography
Inter is chosen for its exceptional legibility and neutral, modern character. It scales perfectly from dense data lists to massive hero headlines.

- **Scale:** A tight typographic scale ensures that information density is managed effectively.
- **Weights:** Use 700 (Bold) for primary headlines to establish authority. Use 400 (Regular) for body copy to ensure comfortable reading of long-form feature descriptions.
- **Rhythm:** Generous line heights are applied to body text to prevent the UI from feeling cluttered, particularly in information-heavy search results.

## Layout & Spacing
This design system uses a **12-column fluid grid** for desktop and a **single-column fluid layout** for mobile. 

- **Hero Alignment:** The primary search call-to-action (CTA) is always center-aligned or left-aligned within the upper 40% of the viewport.
- **Sectioning:** Large vertical spacing (`80px`) separates distinct functional areas (e.g., Search from Feature Cards) to allow the user's eyes to rest.
- **Mobile Adjustments:** Margins shrink to `16px` on mobile devices, and multi-column card layouts reflow into a vertical stack to maintain legibility.

## Elevation & Depth
Depth is created through **Tonal Layers** and **Ambient Shadows** rather than heavy borders.

- **Surface Levels:** The base background is white. Cards and floating elements use a very soft, diffused shadow (0px 4px 20px rgba(0, 0, 0, 0.05)) to appear slightly elevated.
- **The Search Bar:** As the primary tool, the search bar should have the highest elevation, using a more pronounced shadow or a subtle primary-colored glow when focused.
- **Secondary Containers:** Use the neutral gray (`#F2F2F2`) to "inset" sections like footer areas or secondary feature lists, creating depth without adding shadows.

## Shapes
The shape language is "Rounded"—approachable and modern, but not overly casual. 

- **Corner Radius:** A standard `0.5rem (8px)` radius is applied to buttons, input fields, and cards.
- **Search Bar:** The main hero search bar can utilize a `rounded-xl` or `32px` radius to distinguish it from other interactive elements and make it feel more inviting to use.
- **Icons:** Use a consistent 2px stroke width with slightly rounded terminals to match the typography.

## Components
- **Search Bar (Primary CTA):** A large, high-contrast field. It includes a prominent search icon, a clear "Search" button in Primary Blue, and a subtle light-gray background to distinguish it from the white hero section.
- **Buttons:**
    - *Primary:* Solid Primary Blue with white text.
    - *Secondary:* Ghost style with a Deep Navy border or solid Deep Navy.
- **Feature Cards:** White containers with soft shadows. Icons should be placed in small, circular Primary Blue tinted backgrounds (10% opacity) to create visual interest.
- **Trust Signals:** Use small, badge-like "Chips" with green or blue success colors to indicate verified numbers or secure features.
- **Input Fields:** Minimalist design with a 1px border that turns Primary Blue upon focus. Labels should be small and positioned above the field.
- **Lists:** Clean, horizontal rows with 1px light-gray dividers. High information density is acceptable here, provided typographic hierarchy is strictly maintained.