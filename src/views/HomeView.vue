<template>
  <PixelBackground>
    <AppHeader />
    <main>
      <HeroSection />
      <IntroSection />
      <HomeFeed />
      <FeaturedWorks />
      <StatsSection />
      <ServerStatus />
      <SocialSection />
    </main>
    <AppFooter />
  </PixelBackground>
</template>

<script setup lang="ts">
import PixelBackground from '@/components/layout/PixelBackground.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import HeroSection from '@/components/home/HeroSection.vue'
import IntroSection from '@/components/home/IntroSection.vue'
import HomeFeed from '@/components/home/HomeFeed.vue'
import FeaturedWorks from '@/components/home/FeaturedWorks.vue'
import StatsSection from '@/components/home/StatsSection.vue'
import ServerStatus from '@/components/home/ServerStatus.vue'
import SocialSection from '@/components/home/SocialSection.vue'
</script>
