import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import MyPets from './myPetScreen';
import PetDetailScreen from './petDetails'; 
import EditPetScreen from './petEdit'; 
import AddPet from './addPetScreen';

const Stack = createNativeStackNavigator();

export default function PetStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="MyPetsList" component={MyPets} options={{ title: 'My Pets' }} />
      <Stack.Screen name="PetDetail" component={PetDetailScreen} options={{ title: 'Pet Details' }}/>
      <Stack.Screen name="EditPet" component={EditPetScreen} options={{ title: 'Edit Pet' }} />
      <Stack.Screen name="AddPet" component={AddPet} options={{ title: 'Add New Pet' }} />
    </Stack.Navigator>
  );
}